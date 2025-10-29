from dotenv import load_dotenv
from google import genai
import sys
import os
import json
from pathlib import Path
from string import Template

load_dotenv(dotenv_path='../.env')

def build_prompt(payload: dict) -> str:
    """
    Expects payload in this form:

    {
      "experts": [
        { "expert_id": 1, "name": "Alice", "job": "Backend Engineer", "description": "...", "thoughts": "..." },
        { "expert_id": 2, "name": "Bob", "job": "UX Designer", "description": "...", "thoughts": "..." }
      ],
      "project": {
        "title": "Project Title",
        "description": "Project Description",
        "messages": [
          { "expert_id": 1, "content": "Some message text" },
          { "expert_id": 2, "content": "Another message text" },
          { "expert_id": null, "content": "A users message text" }
        ]
      }
    }
    """

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

    # Load prompt template from sibling file and substitute placeholders
    template_path = Path(__file__).with_name('get_summary_prompt.tmpl')
    if not template_path.exists():
        raise FileNotFoundError(f"Prompt template not found: {template_path}")

    template_text = template_path.read_text(encoding='utf-8')
    tmpl = Template(template_text)

    prompt = tmpl.safe_substitute(
        expert_section=expert_section,
        title=title or "",
        description=description or "",
        messages_section=messages_section,
    )

    return prompt.strip()


def generate_summary(payload: dict) -> str:
    api_key = os.getenv("GEMINI_API_KEY")
    model_name = os.getenv("GEMINI_MODEL", "gemini-1.5-flash")
    if not api_key:
        raise ValueError("GEMINI_API_KEY is not set")

    client = genai.Client(api_key=api_key)
    prompt = build_prompt(payload)

    response = client.models.generate_content(
        model=model_name,
        contents=prompt
    )

    return response.text.strip()


def main():
    if len(sys.argv) < 2:
        print("Usage: python summary_gen.py '<input_json>'", file=sys.stderr)
        sys.exit(1)

    try:
        payload = json.loads(sys.argv[1])
    except json.JSONDecodeError:
        print("Invalid JSON payload", file=sys.stderr)
        sys.exit(1)

    try:
        result_text = generate_summary(payload)
        # Prefer valid JSON output; otherwise print raw text to aid debugging
        try:
            parsed = json.loads(result_text)
            print(json.dumps(parsed, ensure_ascii=False))
        except json.JSONDecodeError:
            print(result_text)
    except Exception as e:
        print(f"Error: {str(e)}", file=sys.stderr)
        sys.exit(1)


if __name__ == "__main__":
    main()
