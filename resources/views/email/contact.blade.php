<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Invitación</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Jost:wght@300;400&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Jost', sans-serif;
        }
        
        .center {
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        @media screen and (min-width:320ox) and (max-width:768px){
            .greeting-container {
                width: 100%;
                background-color: #46653C;
                color: #fff;
                padding: 0.5rem;
                border-radius: 1rem;
                text-align: center;
            }
            
            .container {
                width: 100%;
                background-color: #fff;
                color: #000;
                padding: 0.5rem;
                border-radius: 1rem;
                font-size: 1.2rem;
                text-align: center;
            }
        }
        
        @media screen and (min-width:769px){
            .greeting-container {
                width: 80%;
                background-color: #46653C;
                color: #fff;
                padding: 0.5rem;
                border-radius: 1rem;
                text-align: center;
            }
            
            .container {
                width: 80%;
                background-color: #fff;
                color: #000;
                padding: 0.5rem;
                border-radius: 1rem;
                font-size: 1.2rem;
                text-align: center;
            }
        }
        
        .link {
            color: #46653C;
            text-decoration: none;
        }
        
        .link:hover {
            color: #46653C;
            text-decoration: underline;
        }
        
        .small {
            font-size: 0.8rem;
        }
        
        .bold {
            font-weight: bold;
        }
        
        .message-container {
            width: 100%;
            background-color: #f0f0f0;
            color: #000;
            padding: 0.5rem;
            border-radius: 1rem;
            font-size: 1.2rem;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="center">
        <img src="{{ asset('storage/logoPadiush.png') }}" alt="Logo" style="height: 10rem;">
    </div>
    <div class="center">
        <h1 class="greeting-container">Se ha recibido un nuevo mensaje</h1>
    </div>
    <div class="center">
        <div class="container">
            
            <p>{{ $name }} ({{ $email_address }}) envió el siguiente mensaje a través del formulario de contacto de Padiush:</p>
            
            <div class="message-container">
                <p>{{ $text_message }}</p>
            </div>
            
            <p>Atentamente,</p>
            <p class="bold">El equipo de Padiush</p>            
        </div>
    </div>
    <div class="center">
        <div class="greeting-container">
            &copy; Padiush {{ \Carbon\Carbon::now()->format('Y')}}. Todos los derechos reservados.
        </div>
    </div>
    <div class="center">
        <div class="container">
            <p class="small">Este correo electrónico ha sido enviado automáticamente por Padiush. Por favor, no responda a este correo electrónico, ya que se trata de una dirección de envío no monitoreada.</p>
        </div>
    </div>
</body>
</html>