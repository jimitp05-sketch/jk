import re
import os

html_file = r'c:\Users\jimit\Desktop\Merge Gaurd\ai apollo\website 2\reviews.html'
css_file = r'c:\Users\jimit\Desktop\Merge Gaurd\ai apollo\website 2\pulse.css'

with open(html_file, 'r', encoding='utf-8') as f:
    content = f.read()

# Find the specific pulse style tag
match = re.search(r'<style>\s*/\*\s*====== PULSE-PAGE SPECIFIC STYLES ======.*?</style>', content, re.DOTALL)
if match:
    style_content = match.group(0)
    # Strip <style> and </style>
    css_content = re.sub(r'^<style>\s*', '', style_content)
    css_content = re.sub(r'\s*</style>$', '', css_content)
    
    with open(css_file, 'w', encoding='utf-8') as f:
        f.write(css_content.strip())
        
    new_content = content.replace(style_content, '<link rel="stylesheet" href="pulse.css" />')
    with open(html_file, 'w', encoding='utf-8') as f:
        f.write(new_content)
    print("Successfully extracted CSS to pulse.css and updated reviews.html")
else:
    print("Could not find the style tag.")
