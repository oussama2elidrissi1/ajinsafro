#!/usr/bin/env python3
# Fix: Remove return; from diagnosticMode function in voyage-edit-page.js

import re
import sys

file_path = r'public/js/voyage-edit-page.js'

try:
    with open(file_path, 'r', encoding='utf-8') as f:
        content = f.read()
    
    # Try exact string match first
    old_pattern = "(function diagnosticMode() { return;"
    new_pattern = "(function diagnosticMode() {"
    
    if old_pattern in content:
        content = content.replace(old_pattern, new_pattern)
        print(f"✅ SUCCESS: Replaced '{old_pattern}' with '{new_pattern}'")
    else:
        # Try regex with potential whitespace variations
        if re.search(r'diagnosticMode\s*\(\s*\)\s*\{\s*return\s*;', content):
            content = re.sub(r'(diagnosticMode\s*\(\s*\)\s*\{)\s*return\s*;', r'\1', content)
            print("✅ SUCCESS: Regex replacement applied")
        else:
            print("❌ FAILED: Pattern not found")
            sys.exit(1)
    
    with open(file_path, 'w', encoding='utf-8') as f:
        f.write(content)
    
    print("✅ File written successfully")
    sys.exit(0)
    
except Exception as e:
    print(f"❌ ERROR: {e}")
    sys.exit(1)
