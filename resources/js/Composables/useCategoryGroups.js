// Categories don't carry a "group" concept in the database (they're a flat
// list under Category.parent_id), but the design groups them into service
// families for browsing. This maps the seeded slugs to a family; anything
// unmapped falls into "Alte categorii" so new categories never disappear.
const GROUPS = [
    { name: 'Foto & video', slugs: ['fotograf', 'videograf', 'cabina-foto', 'cabina-360'] },
    { name: 'Muzică & divertisment', slugs: ['dj', 'formatie', 'mc'] },
    { name: 'Organizare & locație', slugs: ['wedding-planner', 'restaurant', 'salon-evenimente', 'cazare'] },
    { name: 'Decor & floral', slugs: ['decor', 'florist', 'torturi', 'candy-bar', 'invitatii'] },
    { name: 'Sunet & lumini', slugs: ['sonorizare', 'lumini'] },
    { name: 'Stil & frumusețe', slugs: ['machiaj', 'coafura'] },
    { name: 'Transport', slugs: ['limuzine'] },
];

export function groupCategories(categories) {
    const bySlug = new Map(categories.map((category) => [category.slug, category]));
    const used = new Set();

    const groups = GROUPS
        .map((group) => ({
            name: group.name,
            categories: group.slugs
                .map((slug) => bySlug.get(slug))
                .filter((category) => {
                    if (!category) return false;
                    used.add(category.slug);
                    return true;
                }),
        }))
        .filter((group) => group.categories.length > 0);

    const rest = categories.filter((category) => !used.has(category.slug));
    if (rest.length) {
        groups.push({ name: 'Alte categorii', categories: rest });
    }

    return groups;
}
