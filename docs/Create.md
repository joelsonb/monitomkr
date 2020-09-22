# Create objects
Cria arquivos dos objetos baseados em tabelas

## Opções:
### -c, --connection
Nome da conexão de onde serão lidas as tabelas
> Padrão: conexão padrão configurada na aplicação

### -n, --namespace
Namespace dos arquivo criados.
> Padrão: App

### -t, --table
Nome ou lista de nomes, separados por vírgulas, das tabelas que serão geradas
> Padrão: todas as tabelas da conexão

### --column
Nome ou lista de nomes, separados por vírgulas, das colunas que serão geradas
> Se forem informados nomes de colunas
> Padrão: todas as colunas de cada tabela

<!-- ### --url
A URL usada nas rotas. Se informada em conjunto com mais de uma tabela, será a rota base.
> Padrão: namespace/tabela -->

### --controller-methods
Cria os controllers com os métodos: create, delete, get, update
> Padrão: false

<!-- ### --only-required
Gera somente as colunas obrigatórias de cada tabela
> Padrão: false -->

## Exemplos:
```bash
php mcl mkr:create --connection WinThor -t nome_da_tabela1,nome_da_tabela2
php mcl mkr:create --connection WinThor -t nome_da_tabela -n Teste --controller-methods
```