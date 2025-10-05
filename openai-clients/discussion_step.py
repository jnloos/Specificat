from dotenv import load_dotenv
from openai import OpenAI
import os
import sys
import json
from pathlib import Path
from string import Template

load_dotenv(dotenv_path='../.env')


def generate_discussion_step(payload: dict) -> str:
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

    title = project.get("title")
    description = project.get("description")
    messages = project.get("messages", [])

    # Collect responses from all experts
    results = {}

    for expert in experts:
        try:
            # Call next_message for each expert
            expert_response = generate_next_message(expert, project, messages)
            expert_id = expert_response.get("expert_id")

            # Build the result in the expected format
            results[expert_id] = {
                "statement": expert_response.get("statement", ""),
                "importance": expert_response.get("importance", 5)
            }
        except Exception as e:
            # Handle individual expert errors gracefully
            expert_id = expert.get("id", "unknown")
            print(f"Error processing expert {expert_id}: {str(e)}", file=sys.stderr)
            # Optionally add a fallback response
            results[expert_id] = {
                "statement": "",
                "importance": 0
            }

    return json.dumps(results, indent=2)

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
        result = generate_discussion_step(payload)
        try:
            parsed = json.loads(result)
            print(json.dumps(parsed, ensure_ascii=False))
        except json.JSONDecodeError:
            print(result)
    except Exception as e:
        print(f"Error: {str(e)}", file=sys.stderr)
        sys.exit(1)

if __name__ == "__main__":
    main()
