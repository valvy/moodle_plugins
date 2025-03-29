import os

def verzamel_bestanden(pad, extensies, uitvoer_bestand):
    with open(uitvoer_bestand, 'w', encoding='utf-8') as uitvoer:
        for root, dirs, files in os.walk(pad):
            for bestand in files:
                if any(bestand.endswith(ext) for ext in extensies):
                    volledig_pad = os.path.join(root, bestand)
                    try:
                        with open(fully_path := volledig_pad, 'r', encoding='utf-8') as f:
                            uitvoer.write(f"\n==== {fully_path} ====\n")
                            uitvoer.write(f.read())
                            uitvoer.write("\n\n")
                    except Exception as e:
                        print(f"Fout bij lezen van {volledig_pad}: {e}")

if __name__ == "__main__":
    pad = input("Geef het pad naar de directory: ").strip()
    uitvoer_bestand = "samengevoegd_output.txt"
    verzamel_bestanden(pad, ['.xml', '.php', '.css', '.js'], uitvoer_bestand)
    print(f"Inhoud opgeslagen in: {uitvoer_bestand}")

