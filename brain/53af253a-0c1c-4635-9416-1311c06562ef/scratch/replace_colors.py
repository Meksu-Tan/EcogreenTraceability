import os

def replace_emerald_with_green(directory):
    for root, dirs, files in os.walk(directory):
        for file in files:
            if file.endswith('.vue'):
                file_path = os.path.join(root, file)
                with open(file_path, 'r', encoding='utf-8') as f:
                    content = f.read()
                
                new_content = content.replace('emerald', 'green')
                
                if new_content != content:
                    with open(file_path, 'w', encoding='utf-8') as f:
                        f.write(new_content)
                    print(f"Updated: {file_path}")

if __name__ == "__main__":
    replace_emerald_with_green(r'c:\XAMPP\htdocs\EODS\Master\frontend\src')
