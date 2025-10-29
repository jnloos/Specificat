from dotenv import load_dotenv
from google import genai
import sys
import os

load_dotenv(dotenv_path='../.env')

def main():
    # Read API key from environment variable (recommended)
    api_key = os.getenv("GEMINI_API_KEY")

    # Read model name from environment variable or default to 'gemini-1.5-flash'
    model_name = os.getenv("GEMINI_MODEL", "gemini-1.5-flash")

    # Read prompt from command line argument or use default
    prompt = sys.argv[1] if len(sys.argv) > 1 else "Explain how AI works in a few words"

    # Initialize the Gemini client with the API key
    client = genai.Client(api_key=api_key)

    # Call the generate_content method with the selected model and prompt
    response = client.models.generate_content(
        model=model_name,
        contents=prompt
    )

    # Print the generated text response
    print(response.text)

if __name__ == "__main__":
    main()
