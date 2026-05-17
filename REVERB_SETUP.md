# Laravel Reverb Setup Guide

## 1. Install Composer Dependencies

```bash
composer require laravel/reverb
```

## 2. Environment Variables

Adicione ao seu `.env`:

```env
# Reverb Configuration
REVERB_APP_ID=classup-app
REVERB_APP_KEY=your-reverb-app-key
REVERB_APP_SECRET=your-reverb-app-secret
REVERB_SERVER_HOST=0.0.0.0
REVERB_SERVER_PORT=8080
REVERB_SERVER_HOSTNAME=localhost:8080
REVERB_ALLOWED_ORIGINS=localhost,localhost:3000,127.0.0.1:3000
REVERB_DEBUG=true

# Broadcasting
BROADCAST_DRIVER=reverb
```

## 3. Update .env.example

Adicione ao seu `.env.example` as mesmas variáveis do Reverb.

## 4. Start Reverb Server

```bash
php artisan reverb:start
```

O servidor rodará em `http://localhost:8080`

## 5. Integração com Next.js

### a) Instale as dependências do lado cliente

```bash
npm install laravel-reverb @laravel/echo
```

### b) Configure no seu Next.js (exemplo em app layout ou página)

```javascript
// app/layout.tsx ou app.tsx
'use client'

import { useEffect } from 'react'
import Echo from 'laravel-echo'
import Reverb from 'laravel-reverb'

export default function RootLayout({ children }) {
  useEffect(() => {
    window.Reverb = Reverb
    
    window.Echo = new Echo({
      broadcaster: 'reverb',
      key: process.env.NEXT_PUBLIC_REVERB_APP_KEY || 'your-reverb-app-key',
      wsHost: process.env.NEXT_PUBLIC_REVERB_HOST || 'localhost',
      wsPort: process.env.NEXT_PUBLIC_REVERB_PORT || 8080,
      wssPort: process.env.NEXT_PUBLIC_REVERB_PORT || 8080,
      forceTLS: false, // true em produção
      enabledTransports: ['ws', 'wss'],
    })
  }, [])

  return (
    <html>
      <body>{children}</body>
    </html>
  )
}
```

### c) Adicione ao `.env.local` do Next.js

```env
NEXT_PUBLIC_REVERB_APP_KEY=your-reverb-app-key
NEXT_PUBLIC_REVERB_HOST=localhost
NEXT_PUBLIC_REVERB_PORT=8080
```

### d) Use em um componente

```javascript
'use client'

import { useEffect } from 'react'

export default function MyComponent() {
  useEffect(() => {
    // Escutar em um canal público
    window.Echo.channel('notifications')
      .listen('MyEvent', (e) => {
        console.log('Evento recebido:', e)
      })

    // Ou canal privado (requer autenticação)
    window.Echo.private('users.1')
      .listen('MessageSent', (e) => {
        console.log('Mensagem:', e)
      })
  }, [])

  return <div>Aguardando eventos...</div>
}
```

## 6. Backend - Broadcast Events

No Laravel, crie um evento broadcastable:

```bash
php artisan make:event MyEvent
```

```php
<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MyEvent implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public $message;

    public function __construct($message)
    {
        $this->message = $message;
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('notifications'),
        ];
    }

    public function broadcastAs()
    {
        return 'MyEvent';
    }
}
```

Dispare em qualquer lugar:

```php
MyEvent::dispatch('Hello from Laravel!');
```

## 7. Channels (Público vs Privado)

Para canais privados, edite `routes/channels.php`:

```php
Broadcast::channel('users.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});
```

## 8. Troubleshooting

- **Conexão recusada**: Verifique se `php artisan reverb:start` está rodando
- **CORS error**: Verifique `REVERB_ALLOWED_ORIGINS` no `.env`
- **Evento não chegando**: Use `REVERB_DEBUG=true` para ver logs
- **Porta ocupada**: Altere `REVERB_SERVER_PORT` no `.env`

## 9. Produção

Em produção:
- Use `REVERB_SERVER_HOSTNAME=seu-dominio.com`
- Altere `forceTLS: true` no Next.js
- Configure SSL/TLS no Reverb
- Use `.env.production` separado
