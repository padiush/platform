<?php

return [
    'salutation' => 'El equipo de Padiush',
    'no_reply' => 'Este mensaje se envió automáticamente. Por favor no respondas a esta dirección.',

    'invite' => [
        'subject' => 'Te han invitado a un proyecto en Padiush',
        'greeting' => '¡Hola, :name!',
        'introduction' => ':inviter te ha invitado a formar parte del proyecto «:project» en Padiush.',
        'what_is_padiush' => 'Padiush es una plataforma para investigaciones etnobotánicas: permite diseñar entrevistas, registrarlas en campo, reconciliar los nombres locales con taxones aceptados y calcular índices listos para publicar.',
        'existing_explanation' => 'Encontrarás la invitación en el apartado de invitaciones de tus proyectos.',
        'existing_action' => 'Ver mis invitaciones',
        'new_explanation' => 'Según nuestros registros aún no tienes una cuenta. Usa este enlace personal para crearla; no lo compartas con otras personas.',
        'new_action' => 'Crear mi cuenta',
        'expiration' => 'La invitación vence el :date. Después de esa fecha tendrás que pedir una nueva.',
        'ignore' => 'Si no deseas aceptarla, puedes ignorar este correo. No se requiere ninguna acción de tu parte.',
    ],

    'contact' => [
        'subject' => 'Nuevo mensaje de contacto',
        'greeting' => 'Nuevo mensaje desde el formulario de contacto',
        'introduction' => ':name (:email) escribió lo siguiente:',
        'action' => 'Responder a :name',
        'received_at' => 'Recibido el :date.',
    ],
];
