# Create objects
Cria arquivos dos objetos baseados em tabelas

## Paramêtros
Lista de objetos que serão criados:
- controller
- dao
- dto
- model
- route
- test

Dto e Model sempre serão criados junts, mesmo passando apenas um dos dois como parâmetro
> Padrão: sem parâmetro

## Opções
### -c, --connection
Nome da conexão de onde serão lidas as tabelas
> Padrão: conexão padrão configurada na aplicação

### -n, --namespace
Namespace dos arquivo criados.
> Padrão: App

### -t, --table
Nome ou lista de nomes, separados por vírgulas, das tabelas que serão geradas
> Padrão: todas as tabelas da conexão

### --columns
Nome ou lista de nomes, separados por vírgulas, das colunas que serão geradas
> Se forem informados nomes de colunas
> Padrão: todas as colunas de cada tabela

### --base-url
A rota base das tabelas. Se informada '/' a rota será apenas o nome da tabela
> Padrão: namespace

### --controller-methods
Se informado, gera os controllers com os métodos (create, delete, get, update)
> Padrão: false

### --only-required
Se informado, gera os arquivos apenas com as colunas obrigatórias de cada tabela
> Padrão: false

### --no-route
Se informado, não gera as rotas
> Padrão: false

### --no-test
Se informado, não gera os arquivos de teste
> Padrão: false

### f, --force
Força a criação dos arquivos Controller se existirem
> Padrão: false

## Exemplos:
```bash
php mcl mkr:create --connection WinThor -t nome_da_tabela1,nome_da_tabela2

# Cria somente os arquivos Dao e Dto
php mcl mkr:create dao,dto -c WinThor -t nome_da_tabela -n Teste --controller-methods
```
