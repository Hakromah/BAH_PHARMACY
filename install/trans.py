import sys
import json
import os
try:
    from googletrans import Translator
except ImportError:
    print("WARNING: googletrans module not found. Installing...")
    import subprocess
    subprocess.check_call([sys.executable, "-m", "pip", "install", "googletrans==4.0.0-rc1"])
    from googletrans import Translator

in_file = sys.argv[1]
with open(in_file, 'r', encoding='utf-8') as f:
    keys = json.loads(f.read())

t = Translator()

en_dict = {}
fr_dict = {}
for k, v in keys.items():
    if v.strip() == '':
        continue
    try:
        en_dict[k] = t.translate(v, src='tr', dest='en').text
        fr_dict[k] = t.translate(v, src='tr', dest='fr').text
    except Exception as e:
        en_dict[k] = v
        fr_dict[k] = v

with open(in_file + '_out.json', 'w', encoding='utf-8') as f:
    f.write(json.dumps({'en': en_dict, 'fr': fr_dict}))
print("DONE")