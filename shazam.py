import asyncio
from shazamio import Shazam
import json
import sys

async def main(file_name):
    shazam = Shazam()
    out = await shazam.recognize(file_name)
    x = json.dumps(out, indent=4)
    print(x)

if __name__ == "__main__":
    if len(sys.argv) != 2:
        print("Usage: python shazam.py music_file_name")
        sys.exit(1)
    music_file_name = sys.argv[1]
    loop = asyncio.get_event_loop()
    loop.run_until_complete(main(music_file_name))