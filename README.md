# Módulo PagSeguro Transparente para Magento 2

### Instalação
#### 1. Autorize sua loja 
Acesse http://r-martins.github.io/PagSeguro-Magento-Transparente/magento2/wizard.html e autorize sua loja PagSeguro.

#### 2. Instale o módulo via composer
 
    composer require ricardomartins/pagseguro:dev-master
    bin/magento cache:clean
    bin/magento setup:upgrade
    bin/magento setup:di:compile

#### 3. Configure o Magento

* Altere a quantidade de linhas de endereço em Lojas->Configurações->Clientes->Configurações->Nome e opções de endereço->Número de linhas no endereço.
Altere para 4 linhas.

* Em Formas de Pagamento, configure o e-mail da conta PagSeguro, Token PagSeguro e Public Key obtida no passo 1.

* Em _Stores > Order Status_, configure a loja para exibir pedidos com status Pagamento Pendente no frontend (opcional). [Saiba mais](https://pagsegurotransparente.zendesk.com/hc/pt-br/articles/360029981831).

* Limpe o cache, e pronto!


Se preferir, assista o passo a passo de instalação:

[![ASSISTA O PASSO A PASSO DE INSTALAÇÃO](https://img.youtube.com/vi/DQJ3W9Qfn58/0.jpg)](https://www.youtube.com/watch?v=DQJ3W9Qfn58)

### Site Oficial
http://r-martins.github.io/PagSeguro-Magento-Transparente/

### Central de ajuda e suporte
https://pagsegurotransparente.zendesk.com/hc/pt-br/

### Bugs?
https://github.com/r-martins/PagSeguro-Magento-Transparente-M2/issues

### Autor
Ricardo Martins / [Magenteiro.com](https://www.magenteiro.com/cursos) / [Contribuidores especiais](https://github.com/r-martins/PagSeguro-Magento-Transparente-M2/pulls?utf8=%E2%9C%93&q=is%3Apr+is%3Amerged)
