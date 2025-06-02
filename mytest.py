from apify_client import ApifyClient

# Initialize the ApifyClient
apify_client = ApifyClient('apify_api_fpIjnQWJ5CObzAMgshHHiYi8dpyegS4eQtBR')

# Get the dataset
dataset = apify_client.dataset('qRMQsSGC5YVV4W8YJ')

# Get data from the dataset
dataset_items = dataset.list_items()

# Print captions (if available)
for item in dataset_items.items:
    caption = item.get('caption')
    if caption:
        print(caption)
    else:
        print("[No caption available]")
