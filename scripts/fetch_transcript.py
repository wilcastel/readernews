import sys
import json
from youtube_transcript_api import YouTubeTranscriptApi
from youtube_transcript_api.formatters import TextFormatter

def get_transcript(video_id):
    try:
        # Try to fetch transcript (prefer manual, fallback to auto-generated)
        # ADAPTATION: Version 1.2.3 seemingly uses 'list' instead of 'list_transcripts'
        # Try to fetch transcript (prefer manual, fallback to auto-generated)
        # ADAPTATION: Version 1.2.3 seemingly uses 'list' instead of 'list_transcripts'
        
        if hasattr(YouTubeTranscriptApi, 'list_transcripts'):
             transcript_list = YouTubeTranscriptApi.list_transcripts(video_id)
        else:
             api = YouTubeTranscriptApi()
             transcript_list = api.list(video_id)
        
        # Prefer manually created transcripts, then auto-generated
        # Prefer English, Spanish
        try:
            transcript = transcript_list.find_manually_created(['es', 'en', 'es-419'])
        except:
            try:
                transcript = transcript_list.find_generated(['es', 'en', 'es-419'])
            except:
                # Fallback to whatever is available
                transcript = transcript_list.find_transcript(['es', 'en', 'es-419'])
        
        fetched_transcript = transcript.fetch()
        
        formatter = TextFormatter()
        text_formatted = formatter.format_transcript(fetched_transcript)
        
        # Output strictly the text
        print(text_formatted)
        
    except Exception as e:
        sys.stderr.write(str(e))
        sys.exit(1)

if __name__ == "__main__":
    if len(sys.argv) < 2:
        sys.stderr.write("Error: Video ID required")
        sys.exit(1)
        
    # Set stdout to utf-8 to avoid charmap errors
    sys.stdout.reconfigure(encoding='utf-8')
    get_transcript(sys.argv[1])
