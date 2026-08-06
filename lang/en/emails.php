<?php

return [
    'salutation' => 'The Padiush team',
    'no_reply' => 'This message was sent automatically. Please do not reply to this address.',

    'invite' => [
        'subject' => 'You have been invited to a project on Padiush',
        'greeting' => 'Hello, :name!',
        'introduction' => ':inviter has invited you to join the project “:project” on Padiush.',
        'what_is_padiush' => 'Padiush is a platform for ethnobotanical research: it lets you design interviews, record them in the field, reconcile local names against accepted taxa and calculate publication-ready indices.',
        'existing_explanation' => 'You will find the invitation under the invitations section of your projects.',
        'existing_action' => 'View my invitations',
        'new_explanation' => 'Our records show you do not have an account yet. Use this personal link to create one; please do not share it with anyone else.',
        'new_action' => 'Create my account',
        'expiration' => 'The invitation expires on :date. After that you will need to request a new one.',
        'ignore' => 'If you would rather not accept it, you can ignore this email. No action is needed on your part.',
    ],

    'contact' => [
        'subject' => 'New contact message',
        'greeting' => 'New message from the contact form',
        'introduction' => ':name (:email) wrote the following:',
        'action' => 'Reply to :name',
        'received_at' => 'Received on :date.',
    ],
];
