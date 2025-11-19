import os
import json
import hashlib

# Directory containing the language JSON files
DIR_NAME = './languages'

def get_translations_files(dir_name):
    """Returns a list of all JSON files in the given directory."""
    return [f for f in os.listdir(dir_name) if os.path.isfile(os.path.join(dir_name, f)) and f.endswith('.json')]

def update_file_names(files):
    """For each file, read its 'source' key, create an MD5 hash from it, and rename the file."""
    for f in files:
        with open(os.path.join(DIR_NAME, f), 'r') as json_file:
            data = json.load(json_file)
            if 'source' in data:
                source = None

                if data['source'] == 'blocks/src/calendar/edit.js':
                    source = 'calendar'
                elif data['source'] == 'blocks/src/navigation/edit.js':
                    source = 'navigation'
                elif data['source'] == 'blocks/src/news/edit.js':
                    source = 'news'
                elif data['source'] == 'blocks/src/title/edit.js':
                    source = 'title'
                elif data['source'] == 'resources/javascript/myclub_sections_settings.js':
                    new_name = f"myclub-sections-sv_SE-myclub_sections_settings_js.json"
                    os.rename(os.path.join(DIR_NAME, f), os.path.join(DIR_NAME, new_name))
                    continue

                if source:
                    new_name = f"myclub-sections-sv_SE-myclub-sections-{source}-editor-script.json"
                    os.rename(os.path.join(DIR_NAME, f), os.path.join(DIR_NAME, new_name))

if __name__ == "__main__":
    files = get_translations_files(DIR_NAME)
    update_file_names(files)
