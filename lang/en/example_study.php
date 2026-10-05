<?php

/*
 * The example study's words: the project, its form, the options an informant
 * chooses from, the plants' local names and what the field records say. The
 * place names (El Rosario, its communities) and the people are proper nouns
 * and stay as they are in every language.
 */
return [
    'name' => 'Example: useful plants of the highlands',
    'institution' => 'Example project',

    'form' => [
        'name' => 'Ethnobotanical interview (demonstration)',
        'description' => 'Example instrument: informant details and the uses reported for each species.',
    ],

    'sections' => [
        'informant' => [
            'name' => 'Informant details',
            'description' => 'General information about the person interviewed.',
        ],
        'uses' => [
            'name' => 'Reported uses',
            'description' => 'One set for each plant mentioned.',
        ],
    ],

    // Each question's label, and the short name its answers carry in exports.
    'items' => [
        'age' => ['label' => 'Age', 'name' => 'age'],
        'community' => ['label' => 'Community', 'name' => 'community'],
        'residence' => ['label' => 'Years of residence', 'name' => 'residence'],
        'plant' => ['label' => 'Local name of the plant', 'name' => 'plant'],
        'category' => ['label' => 'Use category', 'name' => 'category'],
        'part' => ['label' => 'Part used', 'name' => 'part'],
        'preparation' => ['label' => 'Preparation', 'name' => 'preparation'],
    ],

    'categories' => [
        'medicinal' => 'Medicinal',
        'food' => 'Food',
        'construction' => 'Construction',
        'fuel' => 'Fuel',
        'ritual' => 'Ritual',
        'craft' => 'Handicraft',
    ],

    'parts' => [
        'leaf' => 'Leaf',
        'bark' => 'Bark',
        'fruit' => 'Fruit',
        'root' => 'Root',
        'flower' => 'Flower',
        'stem' => 'Stem',
        'seed' => 'Seed',
    ],

    'preparations' => [
        'infusion' => 'Infusion',
        'decoction' => 'Decoction',
        'poultice' => 'Poultice',
        'raw' => 'Eaten fresh',
        'maceration' => 'Maceration',
    ],

    // Local names, by scientific name. A plant with no common name in English
    // keeps the one it is known by where the study is set.
    'species' => [
        'Psidium guajava' => 'guava',
        'Matricaria chamomilla' => 'chamomile',
        'Citrus aurantiifolia' => 'lime',
        'Zingiber officinale' => 'ginger',
        'Persea americana' => 'avocado',
        'Moringa oleifera' => 'moringa',
        'Annona muricata' => 'soursop',
        'Cecropia obtusifolia' => 'trumpet tree',
        'Bursera simaruba' => 'gumbo-limbo',
        'Ocimum basilicum' => 'basil',
        'Cedrela odorata' => 'Spanish cedar',
        'Tagetes erecta' => 'marigold',
        'Crescentia alata' => 'calabash tree',
        'Justicia carthaginensis' => 'chichipince',
    ],

    'permit_notes' => 'Collection of botanical material for ethnobotanical research.',
    'repository' => 'El Rosario community herbarium',

    'localities' => [
        'coffee' => 'Highland coffee farm, El Rosario canton',
        'stream' => 'Stream bank, El Rosario canton',
        'trail' => 'Trail to the lookout, El Rosario canton',
        'garden' => 'Home garden, El Rosario canton',
    ],

    'observation' => [
        'name' => 'cortez blanco',
        'notes' => 'Standing tree, pointed out during the walk. Not collected.',
    ],
];
