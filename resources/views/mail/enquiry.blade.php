Name: {{ $enquiry->name }}
Email: {{ $enquiry->email }}
Company: {{ $enquiry->company ?? '-' }}
Topic: {{ ucfirst($enquiry->topic) }}
Language: {{ $enquiry->locale }}
@if ($enquiry->campaign)
Page: {{ $enquiry->campaign->name }}
@endif

{{ $enquiry->message }}
