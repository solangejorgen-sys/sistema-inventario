# Inventario Backend

API PHP para o sistema de inventario.

## Railway

1. Crie um repositorio apenas com os ficheiros desta pasta `backend`.
2. No Railway, crie um novo servico a partir desse repositorio.
3. Adicione um banco PostgreSQL no Railway.
4. Configure a variavel `DATABASE_URL` no servico backend usando a URL do PostgreSQL.

O backend cria automaticamente a tabela `produtos` se ela ainda nao existir.

## Local

```bash
docker build -t inventario-backend .
docker run --env-file .env -p 8000:8000 inventario-backend
```
