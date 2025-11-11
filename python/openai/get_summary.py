from dotenv import load_dotenv
from openai import OpenAI
import sys
import os
import json
from pathlib import Path
from string import Template

load_dotenv(dotenv_path="../.env")


def build_prompt(payload: dict) -> str:
    experts = payload.get("experts", [])
    project = payload.get("project", {}) or {}
    title = project.get("title", "")
    description = project.get("description", "")
    messages = project.get("messages", [])

    # Experts section
    expert_lines = []
    for e in experts:
        expert_lines.append(
            f'- [{e.get("expert_id")}] {e.get("name")} — {e.get("job")}\n'
            f'  description: {e.get("description")}\n'
            f'  thoughts: {e.get("thoughts")}'
        )
    expert_section = "\n".join(expert_lines) if expert_lines else "- none"

    # Messages section
    msg_lines = []
    for m in messages:
        eid = m.get("expert_id")
        speaker = f"expert:{eid}" if eid is not None else "user"
        msg_lines.append(f"- {speaker}: {m.get('content', '')}")
    messages_section = "\n".join(msg_lines) if msg_lines else "- none"

    # Template substitution
    template_path = Path(__file__).with_name("get_summary_prompt.tmpl")
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


def generate_summary(payload: dict) -> str:
    api_key = os.getenv("OPENAI_API_KEY")
    if not api_key:
        raise ValueError("OPENAI_API_KEY is not set")

    model_name = os.getenv("OPENAI_MODEL", "gpt-5")
    client = OpenAI(api_key=api_key)
    prompt = build_prompt(payload)

    response = client.chat.completions.create(
        model=model_name,
        messages=[{"role": "user", "content": prompt}],
        response_format={"type": "json_object"}
    )

    return response.choices[0].message.content.strip()


def main():
    if len(sys.argv) < 2:
        print("Usage: python get_summary.py '<input_json>'", file=sys.stderr)
        sys.exit(1)

    try:
        payload = json.loads(sys.argv[1])
    except json.JSONDecodeError:
        print("Invalid JSON payload", file=sys.stderr)
        sys.exit(1)

    try:
        result_text = generate_summary(payload)
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
