@php
    // Prepare data structures for JSON encoding
    // Note: $expert_section and $messages_section are expected to be arrays or objects passed to the view.
    // If they are JSON strings, they should be decoded before passing to this view or handled carefully.
    // Assuming here they are passed as PHP arrays/objects ready for encoding.

    $projectFields = [
        "title" => $title,
        "description" => $description
    ];

    $outputSchema = [
        "statement_description" => "Expert's next contribution phrased in the first person, focusing on user stories, features, goals, or requirements (not implementation details). The message should sound natural, avoid repeated filler phrases, and may include short confirmations or clarifying questions to other experts.",
        "importance_description" => "Score 1–10 integer, determined by: (1) if the expert has already spoken recently, lower the score; (2) weight and relevance of the content; (3) importance of the expert’s role; (4) how often the expert has spoken so far; (5) whether the message fits naturally into the context without being out-of-place; (6) occasional short confirmations or clarifying questions should also receive higher priority to keep the dialogue authentic."
    ];
@endphp
{
    "template_variables": {
        "expert_section": "placeholder_handled_via_experts_key",
        "title": {!! json_encode($title) !!},
        "description": {!! json_encode($description) !!},
        "messages_section": "placeholder_handled_via_messages_key"
    },
    "discussion_config": {
        "prompt_base": "You are orchestrating a human-like expert discussion. Each expert has a role, description, and private thoughts. The project has a title and description. The purpose is requirements analysis: experts must identify, debate, and refine user stories, features, and opportunities for the target system. User messages should be targeted in the discussion with high priority. They must not propose or plan implementations, tech stacks, APIs, or file structures. Each expert always speaks in the first person ('I' form), expressing their own perspective, opinions, and questions. The dialogue should feel like a natural workshop: experts react directly to each other, agree or disagree, and ask clarifying or follow-up questions. Very short interjections are strongly encouraged — such as simple confirmations ('I agree with you'), quick objections ('I don’t think that works'), or short questions ('please explain this'). These short contributions are valid answers and should appear regularly to make the discussion feel authentic. To enforce fair turn-taking, the expert who spoke last may not speak again immediately; at least one other expert must contribute first. No expert may speak more than two times in a row. Quieter experts should be pulled forward so everyone participates. Experts must also pay attention to their wording: avoid overusing filler phrases or repeating the same expressions so that the dialogue sounds natural, varied, and realistic. Keep the focus strictly on requirements, user needs, goals, and constraints. Each generated message must also include an 'importance' score between 1–10. Output in JSON format",
        "project_fields": {!! json_encode($projectFields, JSON_PRETTY_PRINT) !!}
    },
    "experts": {!! json_encode($expert_section, JSON_PRETTY_PRINT) !!},
    "messages": {!! json_encode($messages_section, JSON_PRETTY_PRINT) !!},
    "output_schema": {!! json_encode($outputSchema, JSON_PRETTY_PRINT) !!}
}
