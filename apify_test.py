from apify_client import ApifyClient
import os
from openai import OpenAI
from datetime import datetime
import requests
import base64
from pathlib import Path
from dotenv import load_dotenv
from requests.auth import HTTPBasicAuth
import time
import csv
import json
from twilio.rest import Client

# Load environment variables
load_dotenv()

# Initialize the clients
apify_client = ApifyClient(os.getenv('APIFY_TOKEN'))
openai_client = OpenAI(api_key=os.getenv('OPENAI_API_KEY'))
twilio_client = Client(os.getenv('TWILIO_ACCOUNT_SID'), os.getenv('TWILIO_AUTH_TOKEN'))

# Twilio credentials
TWILIO_ACCOUNT_SID = os.getenv('TWILIO_ACCOUNT_SID')
TWILIO_AUTH_TOKEN = os.getenv('TWILIO_AUTH_TOKEN')
TWILIO_MESSAGING_SERVICE_SID = os.getenv('TWILIO_MESSAGING_SERVICE_SID')

# Phone numbers to send the report to
PHONE_NUMBERS = [
    '+15618183829',
    '+15612366210',
    '+15612368613'
]

# Create images directory if it doesn't exist
IMAGES_DIR = Path('fishing_images')
IMAGES_DIR.mkdir(exist_ok=True)

def download_image(url, post_id):
    """Download image and return local path"""
    try:
        response = requests.get(url, timeout=10)
        if response.status_code == 200:
            # Create a filename based on post ID
            image_path = IMAGES_DIR / f"post_{post_id}.jpg"
            with open(image_path, 'wb') as f:
                f.write(response.content)
            print(f"Successfully downloaded image for post {post_id}")
            return image_path
        else:
            print(f"Failed to download image for post {post_id}: Status code {response.status_code}")
            return None
    except Exception as e:
        print(f"Error downloading image for post {post_id}: {str(e)}")
        return None

def encode_image(image_path):
    """Encode image to base64"""
    with open(image_path, "rb") as image_file:
        return base64.b64encode(image_file.read()).decode('utf-8')

def analyze_with_gpt4(posts):
    # Prepare the prompt with post information
    prompt = "Generate a concise fishing report (less than 250 words) based on these recent posts. Include location, fish species, and conditions:\n\n"
    
    for post in posts:
        if 'error' not in post:
            date = post.get('timestamp', 'Unknown date')
            location = post.get('locationName', 'Unknown location')
            caption = post.get('caption', '')
            prompt += f"Location: {location}\nDate: {date}\nPost: {caption}\n\n"

    # Analyze with GPT-4
    try:
        response = openai_client.chat.completions.create(
            model="gpt-4",
            messages=[
                {"role": "system", "content": "You are a professional fishing report writer. Create concise, informative reports focusing on fishing conditions, catches, and locations."},
                {"role": "user", "content": prompt}
            ]
        )
        return response.choices[0].message.content
    except Exception as e:
        return f"Error generating report: {str(e)}"

def analyze_images_with_gpt4(posts):
    image_analysis = []
    
    for post in posts:
        if 'error' not in post and 'displayUrl' in post:
            # Download the image
            image_path = download_image(post['displayUrl'], post.get('id', 'unknown'))
            
            if image_path and image_path.exists():
                try:
                    # Encode the image
                    base64_image = encode_image(image_path)
                    
                    response = openai_client.chat.completions.create(
                        model="gpt-4-turbo",
                        messages=[
                            {
                                "role": "user",
                                "content": [
                                    {"type": "text", "text": "What fish species and fishing conditions can you see in this image? Be brief."},
                                    {
                                        "type": "image_url",
                                        "image_url": {
                                            "url": f"data:image/jpeg;base64,{base64_image}"
                                        }
                                    }
                                ]
                            }
                        ],
                        max_tokens=100
                    )
                    analysis = response.choices[0].message.content
                    image_analysis.append(f"Image from {post.get('locationName', 'unknown location')}: {analysis}")
                except Exception as e:
                    print(f"Error analyzing image: {str(e)}")
    
    return image_analysis

def send_sms(phone_number, message):
    """Send SMS using Twilio"""
    try:
        # Check if the message is too long and split it if necessary
        if len(message) > 1600:  # Twilio's limit is 1600 characters
            parts = [message[i:i+1600] for i in range(0, len(message), 1600)]
            for i, part in enumerate(parts, 1):
                print(f"Sending part {i} of {len(parts)} to {phone_number}...")
                message = twilio_client.messages.create(
                    body=part,
                    messaging_service_sid=os.getenv('TWILIO_MESSAGING_SERVICE_SID'),
                    to=phone_number
                )
                print(f"Message SID: {message.sid}")
                print(f"Message status: {message.status}")
                print(f"Successfully sent part {i} to {phone_number}")
        else:
            print(f"Sending message to {phone_number}...")
            message = twilio_client.messages.create(
                body=message,
                messaging_service_sid=os.getenv('TWILIO_MESSAGING_SERVICE_SID'),
                to=phone_number
            )
            print(f"Message SID: {message.sid}")
            print(f"Message status: {message.status}")
            print(f"Successfully sent message to {phone_number}")
    except Exception as e:
        print(f"Error sending SMS to {phone_number}: {str(e)}")
        if hasattr(e, 'more_info'):
            print(f"More info: {e.more_info}")
        if hasattr(e, 'code'):
            print(f"Error code: {e.code}")
        if hasattr(e, 'status'):
            print(f"HTTP status: {e.status}")

def get_instagram_links_from_csv():
    """Read Instagram links from CSV file"""
    links = []
    try:
        with open('test.csv', 'r') as file:
            csv_reader = csv.DictReader(file)
            for row in csv_reader:
                if row['Instagram Link']:
                    links.append(row['Instagram Link'])
        return links
    except Exception as e:
        print(f"Error reading CSV file: {e}")
        return []

def run_instagram_scraper():
    """Run the Instagram scraper actor and return the dataset ID"""
    print("Starting Instagram scraper...")
    
    # Get Instagram links from CSV
    instagram_links = get_instagram_links_from_csv()
    if not instagram_links:
        raise Exception("No Instagram links found in CSV file")
    
    # Define the input for the Instagram scraper
    run_input = {
        "addParentData": False,
        "directUrls": instagram_links,
        "enhanceUserSearchWithFacebookPage": False,
        "isUserReelFeedURL": False,
        "isUserTaggedFeedURL": False,
        "onlyPostsNewerThan": "3 days",
        "resultsLimit": 1,
        "resultsType": "posts",
        "searchLimit": 1,
        "searchType": "hashtag"
    }
    
    # Run the actor
    run = apify_client.actor("apify/instagram-scraper").call(run_input=run_input)
    
    # Wait for the run to complete
    while True:
        run_status = apify_client.run(run["id"]).get()
        if run_status["status"] == "SUCCEEDED":
            break
        elif run_status["status"] == "FAILED":
            raise Exception("Instagram scraper run failed")
        time.sleep(5)
    
    # Get the dataset ID
    dataset_id = run["defaultDatasetId"]
    print(f"Instagram scraper completed. Dataset ID: {dataset_id}")
    return dataset_id

# Run the Instagram scraper and get fresh data
print("Fetching data from Instagram...")
dataset_id = run_instagram_scraper()

# Get data from the dataset
print("Fetching data from Apify dataset...")
dataset = apify_client.dataset(dataset_id)
dataset_items = dataset.list_items().items

# Analyze posts and images
print("\nAnalyzing posts and images...")
fishing_report = analyze_with_gpt4(dataset_items)
image_insights = analyze_images_with_gpt4(dataset_items)

# Prepare the complete report
complete_report = f"Fishing Report:\n{'='*50}\n{fishing_report}\n\nImage Analysis:\n{'='*50}\n"
for insight in image_insights:
    complete_report += f"- {insight}\n"

# Print the final report
print("\nFishing Report:")
print("=" * 50)
print(fishing_report)

if image_insights:
    print("\nImage Analysis:")
    print("=" * 50)
    for insight in image_insights:
        print(f"- {insight}")

# Send the report via SMS
print("\nSending report via SMS...")
for phone_number in PHONE_NUMBERS:
    send_sms(phone_number, complete_report) 