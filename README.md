# Loki

Your lowkey ai trickster for the Craft CMS 5 Control Panel. Adds AI text generation to field editing, and supports multiple LLM providers (OpenAI, Anthropic, DeepL)..

Important: The plugin handle is "craft-loki"
This handle is needed for cli tasks e.g. migrations `ddev exec php craft migrate/up --plugin=craft-loki`

## Installation

Add the minimum stability to your composer.json (as long as the plugin is not yet 1.0)
```
    "minimum-stability": "dev",
    "prefer-stable": true,
```

Add the repository to your composer.json
```
"repositories":[
    {
        "type": "vcs",
        "url" : "git@github.com:sturmundbraem/craft-loki.git",
        "no-api": true
    }
]
```

Run `ddev composer require stubr/craft-loki`

Open the panel `ddev launch panel`, go to Settings -> Plugins and Install the Plugin.

### APIs

API keys can be set in two ways:

1. Plugin Settings page (Settings → Plugins → Loki) — paste keys directly into the form, or reference an environment variable by typing $OPENAI_API_KEY (the field will autosuggest available env vars from your .env).
2. .env file — set OPENAI_API_KEY=sk-..., CLAUDE_API_KEY=sk-ant-..., DEEPL_API_KEY=... and reference them as $OPENAI_API_KEY etc. in the Settings page.

## Usage

### Managing Prompts

Go to the "Loki" section in the CP sidebar. Here you can:

- Add, edit, and remove prompts
- Set which LLM provider each prompt uses (OpenAI, Claude, DeepL)
- Check "All PlainText" or "All CKEditor" to make a prompt available on all fields of that type
- Assign specific prompts to specific fields using the Field Prompt Assignments panel
- Add a general Base Prompt, that tells the AI how to generate text: the tone, structure, etc for all other prompts.

Each prompt has:
- **Label** — short name shown in the dropdown (e.g. "Summarize")
- **Prompt** — full instruction sent to the AI (e.g. "Summarize the page content into one sentence")
- **Provider** — which AI to use for this prompt

### Twig Variables in Prompts

Prompts support the following Twig variables for dynamic content

- `{{ siteLang }}` — the current site's language (e.g. "de", "en")
- `{{ fieldHandle }}` — the field handle being edited
- `{{ entryTitle }}` — the entry's title
- `{{ fields.<fieldHandle> }}` - the specific field user would refer to for more exact prompts 

Example: `Translate this text to {{ siteLang }}` or while editing the subtitle: `Summarise the following text in one sentence: "{{ fields.description }}`

### Using the Wand Button

On any entry edit page, PlainText and CKEditor fields will show a magic wand button next to the field label. Click it to see a dropdown of available prompts. Selecting a prompt will:

1. Send the prompt along with all field content to the selected AI provider
2. Create a draft of the entry with the AI-generated text
3. Redirect you to the draft for review

## Settings

### Prompt Management

Prompts are managed through the "Loki" CP section. They are stored in Craft's project config and automatically synced to `config/project/project.yaml`.

### Field Assignments

In the "AI Prompts" CP section, use the Field Prompt Assignments panel to assign specific prompts to specific fields. Select a field on the left, then check the prompts you want available for that field on the right.

Fields without specific assignments will fall back to prompts marked as "All PlainText" or "All CKEditor".

## Updating

To update to the newest version of this package use `ddev c update stubr/craft-loki`
