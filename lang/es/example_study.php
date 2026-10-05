<?php

/*
 * The example study's words: the project, its form, the options an informant
 * chooses from, the plants' local names and what the field records say. The
 * place names (El Rosario, its communities) and the people are proper nouns
 * and stay as they are in every language.
 */
return [
    'name' => 'Ejemplo: plantas útiles de la cordillera',
    'institution' => 'Proyecto de ejemplo',

    'form' => [
        'name' => 'Entrevista etnobotánica (demostración)',
        'description' => 'Instrumento de ejemplo: datos del informante y usos reportados por especie.',
    ],

    'sections' => [
        'informant' => [
            'name' => 'Datos del informante',
            'description' => 'Información general de la persona entrevistada.',
        ],
        'uses' => [
            'name' => 'Usos reportados',
            'description' => 'Un conjunto por cada planta mencionada.',
        ],
    ],

    // Each question's label, and the short name its answers carry in exports.
    'items' => [
        'age' => ['label' => 'Edad', 'name' => 'edad'],
        'community' => ['label' => 'Comunidad', 'name' => 'comunidad'],
        'residence' => ['label' => 'Años de residencia', 'name' => 'residencia'],
        'plant' => ['label' => 'Nombre local de la planta', 'name' => 'planta'],
        'category' => ['label' => 'Categoría de uso', 'name' => 'categoria'],
        'part' => ['label' => 'Parte utilizada', 'name' => 'parte'],
        'preparation' => ['label' => 'Preparación', 'name' => 'preparacion'],
    ],

    'categories' => [
        'medicinal' => 'Medicinal',
        'food' => 'Alimenticio',
        'construction' => 'Construcción',
        'fuel' => 'Combustible',
        'ritual' => 'Ritual',
        'craft' => 'Artesanal',
    ],

    'parts' => [
        'leaf' => 'Hoja',
        'bark' => 'Corteza',
        'fruit' => 'Fruto',
        'root' => 'Raíz',
        'flower' => 'Flor',
        'stem' => 'Tallo',
        'seed' => 'Semilla',
    ],

    'preparations' => [
        'infusion' => 'Infusión',
        'decoction' => 'Cocimiento',
        'poultice' => 'Emplasto',
        'raw' => 'Consumo directo',
        'maceration' => 'Macerado',
    ],

    // Local names, by scientific name.
    'species' => [
        'Psidium guajava' => 'guayaba',
        'Matricaria chamomilla' => 'manzanilla',
        'Citrus aurantiifolia' => 'limón',
        'Zingiber officinale' => 'jengibre',
        'Persea americana' => 'aguacate',
        'Moringa oleifera' => 'moringa',
        'Annona muricata' => 'guanábana',
        'Cecropia obtusifolia' => 'guarumo',
        'Bursera simaruba' => 'jiote',
        'Ocimum basilicum' => 'albahaca',
        'Cedrela odorata' => 'cedro',
        'Tagetes erecta' => 'flor de muerto',
        'Crescentia alata' => 'morro',
        'Justicia carthaginensis' => 'chichipince',
    ],

    'permit_notes' => 'Recolecta de material botánico con fines de investigación etnobotánica.',
    'repository' => 'Herbario comunitario de El Rosario',

    'localities' => [
        'coffee' => 'Cafetal de altura, cantón El Rosario',
        'stream' => 'Borde de quebrada, cantón El Rosario',
        'trail' => 'Sendero al mirador, cantón El Rosario',
        'garden' => 'Huerto familiar, cantón El Rosario',
    ],

    'observation' => [
        'name' => 'cortez blanco',
        'notes' => 'Árbol en pie, señalado durante el recorrido. No se recolectó.',
    ],
];
