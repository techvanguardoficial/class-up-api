# Docker Setup com Sail + Reverb

## 🚀 Iniciar tudo de uma vez

```bash
docker-compose up -d
```

Isso vai iniciar:
- **Laravel** (porta 80)
- **Reverb** (porta 8080)
- **MySQL** (porta 3306)
- **Redis** (porta 6379)

## 📋 Verificar status dos containers

```bash
docker-compose ps
```

Você deve ver:
```
classup-laravel    php artisan serve        Up
classup-reverb     php artisan reverb:start Up
classup-mysql      mysql server             Up
classup-redis      redis server             Up
```

## 🔍 Ver logs

```bash
# Logs de tudo
docker-compose logs -f

# Logs apenas do Laravel
docker-compose logs -f laravel.test

# Logs apenas do Reverb
docker-compose logs -f reverb

# Logs apenas do MySQL
docker-compose logs -f mysql
```

## 🧹 Parar containers

```bash
docker-compose down
```

## 🔄 Reiniciar um serviço específico

```bash
# Reiniciar Reverb
docker-compose restart reverb

# Reiniciar Laravel
docker-compose restart laravel.test
```

## 💾 Limpar tudo (volumes inclusos)

```bash
docker-compose down -v
```

## 🔧 Executar comandos no container

```bash
# Artisan commands
docker-compose exec laravel.test php artisan migrate
docker-compose exec laravel.test php artisan tinker

# Bash
docker-compose exec laravel.test bash

# Composer
docker-compose exec laravel.test composer install
```

## 📍 URLs de acesso

- **Laravel API**: http://localhost
- **Reverb WebSocket**: ws://localhost:8080
- **MySQL**: localhost:3306
- **Redis**: localhost:6379

## ⚙️ Credenciais

```env
DB_HOST=mysql
DB_DATABASE=classup
DB_USERNAME=classup
DB_PASSWORD=classup123
```

## 🐛 Troubleshooting

### Porta já em uso
```bash
# Mudar porta no .env
APP_PORT=8001  # em vez de 80
REVERB_SERVER_PORT=8081  # em vez de 8080
```

### Conexão recusada
```bash
# Reiniciar containers
docker-compose restart
```

### Volumes presos
```bash
# Remover volumes órfãos
docker-compose down --remove-orphans
```

## 📦 Reconstruir imagens

```bash
docker-compose up -d --build
```

## 🔐 Em Produção

Para usar em produção, crie um arquivo `.env.production` com:
- `APP_DEBUG=false`
- `REVERB_SERVER_HOSTNAME=seu-dominio.com`
- `REVERB_DEBUG=false`
- URLs HTTPS
