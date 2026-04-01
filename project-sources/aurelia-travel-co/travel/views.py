from django.shortcuts import render


def index(request):
    journeys = [
        {
            'title': 'Aegean Sun Escape',
            'tag': 'Greece',
            'detail': 'Private sailing, cliffside suites, and slow island mornings curated end to end.'
        },
        {
            'title': 'Kyoto & The Inland Sea',
            'tag': 'Japan',
            'detail': 'Tea ceremonies, ryokan stays, and artful rail connections with a local guide.'
        },
        {
            'title': 'Sahara to the Souks',
            'tag': 'Morocco',
            'detail': 'Desert camps under the stars, atelier visits, and rooftop dinners in the medina.'
        },
    ]

    benefits = [
        {
            'title': 'Tailored itineraries',
            'detail': 'Every route is built around your pace, tastes, and preferred level of adventure.'
        },
        {
            'title': 'Trusted partners',
            'detail': 'We work with boutique hotels, expert guides, and quiet local favorites.'
        },
        {
            'title': 'Quiet luxury',
            'detail': 'Thoughtful details, seamless transfers, and room to breathe in each journey.'
        },
    ]

    stats = [
        {'value': '120+', 'label': 'Journeys planned in 2025'},
        {'value': '28', 'label': 'Countries curated'},
        {'value': '98%', 'label': 'Client return rate'},
    ]

    return render(request, 'index.html', {
        'journeys': journeys,
        'benefits': benefits,
        'stats': stats,
    })


def about(request):
    principles = [
        {
            'title': 'Design-led planning',
            'detail': 'We layer experiences like a great story: contrast, rhythm, and delight.'
        },
        {
            'title': 'Human-first travel',
            'detail': 'Local experts, small moments, and mindful itineraries make the trip feel personal.'
        },
        {
            'title': 'Clarity & care',
            'detail': 'Transparent quotes, flexible planning, and a steady hand if plans change.'
        },
    ]

    milestones = [
        {'year': '2016', 'event': 'Aurelia Travel Co. founded in Lagos and Lisbon.'},
        {'year': '2019', 'event': 'Introduced our signature slow-luxury journeys.'},
        {'year': '2023', 'event': 'Expanded to 20+ artisan partners across Africa and Europe.'},
    ]

    return render(request, 'about.html', {
        'principles': principles,
        'milestones': milestones,
    })


def register(request):
    submitted = False
    form_data = {
        'full_name': '',
        'email': '',
        'phone': '',
        'destination': '',
        'travel_month': '',
        'travelers': '',
        'budget': '',
        'notes': '',
    }

    if request.method == 'POST':
        submitted = True
        form_data = {
            'full_name': request.POST.get('full_name', '').strip(),
            'email': request.POST.get('email', '').strip(),
            'phone': request.POST.get('phone', '').strip(),
            'destination': request.POST.get('destination', '').strip(),
            'travel_month': request.POST.get('travel_month', '').strip(),
            'travelers': request.POST.get('travelers', '').strip(),
            'budget': request.POST.get('budget', '').strip(),
            'notes': request.POST.get('notes', '').strip(),
        }

    return render(request, 'registration.html', {
        'submitted': submitted,
        'form_data': form_data,
    })
