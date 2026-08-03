export function formatListingPrice(listing) {
    const from = Number(listing.price_from);

    if (listing.price_type === 'on_request' || !listing.price_from || Number.isNaN(from)) {
        return 'Preț la cerere';
    }

    const amount = from.toLocaleString('ro-RO');

    if (listing.price_type === 'starting_from') {
        return `de la ${amount} RON`;
    }

    if (listing.price_type === 'per_hour') {
        return `${amount} RON / oră`;
    }

    return `${amount} RON`;
}
