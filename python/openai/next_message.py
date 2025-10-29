import os
import sys
import json
from openai import OpenAI
from string import Template

def generate_next_message(expert: dict, project: dict, messages: list) -> dict:
    """
    Generate the next message for a single expert.

    Args:
        expert: Dictionary containing expert information (id, role, description, private_thoughts)
        project: Dictionary containing project information (title, description)
        messages: List of previous messages in the discussion

    Returns:
        Dictionary with expert_id, statement, and importance score
    """
    client = OpenAI(api_key=os.environ.get("OPENAI_API_KEY"))

    # Load and prepare the template
    template_path = os.path.join(os.path.dirname(__file__), "next_message_prompt.tmpl")
    with open(template_path, "r") as f:
        template_content = f.read()

    template_dict = json.loads(template_content)
    prompt_template = Template(json.dumps(template_dict))

    # Format expert information
    expert_json = json.dumps({
        "id": expert.get("id"),
        "role": expert.get("role"),
        "description": expert.get("description"),
        "private_thoughts": expert.get("private_thoughts", "")
    }, indent=2)

    # Format messages
    messages_json = json.dumps(messages, indent=2)

    # Build the prompt
    prompt = prompt_template.substitute(
        expert_section=expert_json,
        title=project.get("title", ""),
        description=project.get("description", ""),
        messages_section=messages_json
    )

    # Call OpenAI API
    response = client.chat.completions.create(
        model="gpt-5-chat-latest",
        messages=[
            {"role": "system", "content": "You are an expert participating in a requirements analysis discussion. Provide your response in valid JSON format."},
            {"role": "user", "content": prompt}
        ],
        response_format={"type": "json_object"},
        temperature=0.7
    )

    # Parse the response
    result = json.loads(response.choices[0].message.content)

    # Ensure the result includes the expert_id
    expert_id = expert.get("id")
    if expert_id in result:
        return {
            "expert_id": expert_id,
            "statement": result[expert_id].get("statement", ""),
            "importance": result[expert_id].get("importance", 5)
        }
    else:
        # Handle alternative response formats
        return {
            "expert_id": expert_id,
            "statement": result.get("statement", ""),
            "importance": result.get("importance", 5)
        }

if __name__ == "__main__":
    try:
        # Read input from command line argument
        input_json = sys.argv[1]
        payload = json.loads(input_json)

        result = generate_next_message(
            payload["expert"],
            payload["project"],
            payload.get("messages", [])
        )

        print(json.dumps(result))
    except Exception as e:
        print(json.dumps({"error": str(e)}), file=sys.stderr)
        sys.exit(1)
