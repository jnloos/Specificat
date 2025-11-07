import os
import sys
import json
from openai import OpenAI
from string import Template


def generate_next_message(expert: dict, project: dict, messages: list) -> dict:
    """
    Generate the next message for a single expert.

    Args:
        expert: { "expert_id": int, "role": str, "description": str, "private_thoughts": str }
        project: { "title": str, "description": str }
        messages: list of { "expert_id": int|None, "content": str }

    Returns:
        { "expert_id": int, "statement": str, "importance": int }
    """
    api_key = os.getenv("OPENAI_API_KEY")
    if not api_key:
        raise ValueError("OPENAI_API_KEY is not set")

    client = OpenAI(api_key=api_key)

    # Load and prepare the prompt template
    template_path = os.path.join(os.path.dirname(__file__), "next_message_prompt.tmpl")
    if not os.path.exists(template_path):
        raise FileNotFoundError(f"Prompt template not found: {template_path}")

    with open(template_path, "r", encoding="utf-8") as f:
        template_content = f.read()

    prompt_template = Template(template_content)

    # Fill placeholders
    expert_json = json.dumps({
        "expert_id": expert.get("expert_id"),
        "role": expert.get("role"),
        "description": expert.get("description"),
        "private_thoughts": expert.get("private_thoughts", "")
    }, indent=2)

    messages_json = json.dumps(messages, indent=2)

    prompt = prompt_template.substitute(
        expert_section=expert_json,
        title=project.get("title", ""),
        description=project.get("description", ""),
        messages_section=messages_json
    )

    # Query OpenAI
    response = client.chat.completions.create(
        model=os.getenv("OPENAI_MODEL", "gpt-5-chat-latest"),
        messages=[
            {"role": "system", "content": "You are an expert participating in a requirements analysis discussion. Output valid JSON only."},
            {"role": "user", "content": prompt}
        ],
        response_format={"type": "json_object"},
        temperature=0.7
    )

    # Safely parse model output
    try:
        result = json.loads(response.choices[0].message.content)
    except (ValueError, AttributeError):
        raise ValueError("Model returned invalid JSON")

    expert_id = expert.get("expert_id")
    if expert_id in result:
        entry = result[expert_id]
    else:
        entry = result

    return {
        "expert_id": expert_id,
        "statement": entry.get("statement", ""),
        "importance": entry.get("importance", 5)
    }


if __name__ == "__main__":
    try:
        payload = json.loads(sys.argv[1])
        result = generate_next_message(
            payload["expert"],
            payload["project"],
            payload.get("messages", [])
        )
        print(json.dumps(result, ensure_ascii=False))
    except Exception as e:
        print(json.dumps({"error": str(e)}), file=sys.stderr)
        sys.exit(1)
