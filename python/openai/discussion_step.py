from dotenv import load_dotenv
from openai import OpenAI
from next_message import generate_next_message
import os
import sys
import json
from pathlib import Path
from string import Template
import concurrent.futures

load_dotenv(dotenv_path="../.env")

# ---------- Helper: Build joint prompt for "single" mode ----------
def build_joint_prompt(payload: dict) -> str:
    experts = payload.get("experts", [])
    project = payload.get("project", {}) or {}

    title = project.get("title", "")
    description = project.get("description", "")
    messages = project.get("messages", [])

    # Format experts
    expert_lines = [
        f"- [{e.get('expert_id')}] {e.get('name')} — {e.get('job')}\n"
        f"  description: {e.get('description')}\n"
        f"  thoughts: {e.get('thoughts')}"
        for e in experts
    ]
    expert_section = "\n".join(expert_lines) if expert_lines else "- none"

    # Format messages
    msg_lines = []
    for m in messages:
        eid = m.get("expert_id")
        speaker = f"expert:{eid}" if eid is not None else "user"
        msg_lines.append(f"- {speaker}: {m.get('content', '')}")
    messages_section = "\n".join(msg_lines) if msg_lines else "- none"

    # Load template
    template_path = Path(__file__).with_name("discussion_prompt.tmpl")
    if not template_path.exists():
        raise FileNotFoundError(f"Prompt template not found: {template_path}")

    template_text = template_path.read_text(encoding="utf-8")
    tmpl = Template(template_text)

    prompt = tmpl.safe_substitute(
        expert_section=expert_section,
        title=title,
        description=description,
        messages_section=messages_section,
    )

    return prompt.strip()

# ---------- Generation Core ----------
def generate_discussion_step(payload: dict) -> str:
    """
    Unified function supporting both strategies:
      mode='single'  -> joint LLM call for all experts (Gemini-like)
      mode='multi'   -> individual LLM calls per expert (OpenAI-like)
    """
    mode = payload.get("mode") or payload.get("prompt_strategy") or "single"
    experts = payload.get("experts", [])
    project = payload.get("project", {}) or {}
    messages = project.get("messages", [])

    # --- SINGLE MODE: one joint request for all experts ---
    if mode == "single":
        api_key = os.getenv("OPENAI_API_KEY")
        if not api_key:
            raise ValueError("OPENAI_API_KEY not set")

        model_name = os.getenv("OPENAI_MODEL", "gpt-5")
        client = OpenAI(api_key=api_key)

        prompt = build_joint_prompt(payload)
        response = client.chat.completions.create(
            model=model_name,
            messages=[{"role": "user", "content": prompt}],
            response_format={"type": "json_object"}
        )
        return response.choices[0].message.content.strip()

    # --- MULTI MODE: one request per expert (parallelizable) ---
    elif mode == "multiple":
        results = {}

        def run_expert(expert):
            expert_id = expert.get("expert_id")
            try:
                expert_response = generate_next_message(expert, project, messages)
                return expert_id, {
                    "statement": expert_response.get("statement", ""),
                    "importance": expert_response.get("importance", 5)
                }
            except Exception as e:
                print(f"Error processing expert {expert_id}: {str(e)}", file=sys.stderr)
                return expert_id, {"statement": str(e), "importance": 0}

        # Run all experts concurrently for better performance
        with concurrent.futures.ThreadPoolExecutor() as executor:
            for expert_id, result in executor.map(run_expert, experts):
                results[expert_id] = result

        return json.dumps(results, ensure_ascii=False)

    else:
        raise ValueError(f"Unknown mode '{mode}' – use 'single' or 'multiple'.")

# ---------- Entrypoint ----------
def main():
    if len(sys.argv) < 2:
        print("Usage: python discussion_step.py '<input_json>'", file=sys.stderr)
        sys.exit(1)

    try:
        payload = json.loads(sys.argv[1])
    except json.JSONDecodeError:
        print("Invalid JSON payload", file=sys.stderr)
        sys.exit(1)

    try:
        result_text = generate_discussion_step(payload)
        try:
            parsed = json.loads(result_text)
            print(json.dumps(parsed, ensure_ascii=False))
        except json.JSONDecodeError:
            print(result_text)
    except Exception as e:
        print(json.dumps({"error": str(e)}), file=sys.stderr)
        sys.exit(1)


if __name__ == "__main__":
    main()
