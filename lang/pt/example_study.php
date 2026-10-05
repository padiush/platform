<?php

/*
 * The example study's words: the project, its form, the options an informant
 * chooses from, the plants' local names and what the field records say. The
 * place names (El Rosario, its communities) and the people are proper nouns
 * and stay as they are in every language.
 */
return [
    'name' => 'Exemplo: plantas úteis da serra',
    'institution' => 'Projeto de exemplo',

    'form' => [
        'name' => 'Entrevista etnobotânica (demonstração)',
        'description' => 'Instrumento de exemplo: dados do informante e usos relatados por espécie.',
    ],

    'sections' => [
        'informant' => [
            'name' => 'Dados do informante',
            'description' => 'Informações gerais sobre a pessoa entrevistada.',
        ],
        'uses' => [
            'name' => 'Usos relatados',
            'description' => 'Um conjunto para cada planta mencionada.',
        ],
    ],

    // Each question's label, and the short name its answers carry in exports.
    'items' => [
        'age' => ['label' => 'Idade', 'name' => 'idade'],
        'community' => ['label' => 'Comunidade', 'name' => 'comunidade'],
        'residence' => ['label' => 'Anos de residência', 'name' => 'residencia'],
        'plant' => ['label' => 'Nome local da planta', 'name' => 'planta'],
        'category' => ['label' => 'Categoria de uso', 'name' => 'categoria'],
        'part' => ['label' => 'Parte utilizada', 'name' => 'parte'],
        'preparation' => ['label' => 'Preparo', 'name' => 'preparo'],
    ],

    'categories' => [
        'medicinal' => 'Medicinal',
        'food' => 'Alimentício',
        'construction' => 'Construção',
        'fuel' => 'Combustível',
        'ritual' => 'Ritual',
        'craft' => 'Artesanal',
    ],

    'parts' => [
        'leaf' => 'Folha',
        'bark' => 'Casca',
        'fruit' => 'Fruto',
        'root' => 'Raiz',
        'flower' => 'Flor',
        'stem' => 'Caule',
        'seed' => 'Semente',
    ],

    'preparations' => [
        'infusion' => 'Infusão',
        'decoction' => 'Decocção',
        'poultice' => 'Cataplasma',
        'raw' => 'Consumo direto',
        'maceration' => 'Maceração',
    ],

    // Local names, by scientific name. A plant with no common name in
    // Portuguese keeps the one it is known by where the study is set.
    'species' => [
        'Psidium guajava' => 'goiaba',
        'Matricaria chamomilla' => 'camomila',
        'Citrus aurantiifolia' => 'limão',
        'Zingiber officinale' => 'gengibre',
        'Persea americana' => 'abacate',
        'Moringa oleifera' => 'moringa',
        'Annona muricata' => 'graviola',
        'Cecropia obtusifolia' => 'embaúba',
        'Bursera simaruba' => 'jiote',
        'Ocimum basilicum' => 'manjericão',
        'Cedrela odorata' => 'cedro',
        'Tagetes erecta' => 'cravo-de-defunto',
        'Crescentia alata' => 'cabaceira',
        'Justicia carthaginensis' => 'chichipince',
    ],

    'permit_notes' => 'Coleta de material botânico para pesquisa etnobotânica.',
    'repository' => 'Herbário comunitário de El Rosario',

    'localities' => [
        'coffee' => 'Cafezal de altitude, cantão El Rosario',
        'stream' => 'Margem de riacho, cantão El Rosario',
        'trail' => 'Trilha para o mirante, cantão El Rosario',
        'garden' => 'Quintal familiar, cantão El Rosario',
    ],

    'observation' => [
        'name' => 'cortez blanco',
        'notes' => 'Árvore em pé, apontada durante o percurso. Não foi coletada.',
    ],
];
