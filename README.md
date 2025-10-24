COISABOA — Descrição detalhada do projeto (MVP v1.0)
1) Visão geral

COISABOA é um app Android nativo (Kotlin) para compras, vendas e gestão de estoque, 100% offline-first com persistência local via Room e suporte a backup/sincronização em nuvem.
O MVP v1.0 entrega o fluxo completo: Cadastrar/atualizar produtos → Comprar (entrada) → Vender (saída) → Relatórios básicos.

2) Objetivos do MVP v1.0

Operar sem internet (dados locais confiáveis).

Fluxos essenciais funcionando ponta a ponta: Compras, Vendas, Estoque, Relatórios.

Integridade de estoque (não permitir saldo negativo).

UX simples: formulários claros, seleção de produto filtrável, cálculo automático de totais.

Base técnica sólida para escalar (Repos/DAOs padronizados, Room, Coroutines).

3) Arquitetura e stack

Plataforma: Android (Kotlin)

Arquitetura: UI (Activities/Adapters) → ViewModel → Repository → DAO (Room)

Persistência: Room (SQLite) + TypeConverters (Date ⇄ Long)

Assíncrono: Kotlin Coroutines (Dispatchers.IO) + lifecycleScope

UI: AndroidX (AppCompat, Activity KTX, Material), ViewBinding habilitado

Build: Gradle Kotlin DSL (build.gradle.kts), compileSdk=34, minSdk=24, targetSdk=34, jvmTarget=17

Java: JDK Temurin (JAVA_HOME configurado)

Testes de UI base: AndroidX Test/Espresso (setup)

Backup/sync (baseline): mecanismo previsto para upload/export; dados locais são a fonte da verdade

4) Camada de dados (Room)
4.1 AppDatabase

Registra entidades: ProductEntity, PurchaseEntity, SaleEntity (+ UserEntity opcional)

@TypeConverters(Converters::class) para Date

Provider singleton: DatabaseProvider.get(context)

.fallbackToDestructiveMigration() (durante dev)

.setJournalMode(WRITE_AHEAD_LOGGING)

4.2 Entidades (principais)

ProductEntity (product)

id: Long (PK)

nome: String

quantidade: Int

valorEstimado: Double? (custo unitário)

valorRevenda: Double? (preço sugerido)

caminhoImagem: String?

observacoes: String?

PurchaseEntity (purchase)

id: Long

produtoNome: String

quantidade: Int

valorUnitario: Double

valorRevenda: Double?

valorTotal: Double

formaPagamento: String

caminhoImagemProduto: String?

caminhoImagemNota: String?

dataCompra: Date

SaleEntity (sale)

id: Long

produtoNome: String

quantidade: Int

valorUnitario: Double

valorTotal: Double

formaPagamento: String

valorEstimado: Double? (custo de referência)

observacoes: String?

dataVenda: Date

4.3 DAOs

ProductDao

insert(product: ProductEntity): Long (REPLACE)

getAll(), getById(id), getByName(nome)

increaseStock(productId, quantity), decreaseStock(productId, quantity) (protege negativos)

updateQuantidade(id, novaQtd), delete(product)

PurchaseDao

insert(purchase): Long, getAll() ORDER BY dataCompra DESC, getById(id), getByProductName(nome), delete(purchase)

SaleDao

insert(sale): Long, getAll() ORDER BY dataVenda DESC

getTotalSalesValue(): Double?

5) Repositórios (regra de negócio)

Todos os repositórios recebem DAOs, não Context.

ProductRepository

CRUD e controle de estoque (aumentar/diminuir com validação)

getAll/getById/getByName/insertOrUpdate/delete, increaseStock/decreaseStock

PurchaseRepository

insertCompra(purchase)

Se produto existe: aumenta quantidade, atualiza valorEstimado/valorRevenda e imagem

Se não: cria ProductEntity com dados da compra

getAll/getById/getByProductName/delete

SaleRepository

insert(venda) → insere venda e baixa estoque chamando ProductRepository.decreaseStock

getAll()

getTotalSalesValue()

getSalesBetween(inicio, fim) (filtra por dataVenda)

Decisão de domínio: estoque agregado por produto. Itens com o mesmo nome acumulam quantidade no mesmo registro.

6) UI (telas principais)
6.1 Dashboard

Navega para Comprar, Vender, Gerenciar Estoque, Relatórios, Backup e Login/Main

6.2 Comprar (Nova Compra)

Campos: Produto, Quantidade, Valor Unitário, Valor Revenda (opcional), Forma de Pagamento

Imagens: produto e vendedor/nota (salvas via MediaStorage em storage interno; caminho persistido)

Cálculo automático do valor total

Ação: cria PurchaseEntity; PurchaseRepository atualiza/insere produto e aumenta estoque

Validações: campos obrigatórios e valores > 0

6.3 Gerenciar Estoque

Lista de produtos (thumb, nome, qtd, custo/revenda, editar)

Formulário: imagem, nome, quantidade, custo (estimado), revenda, observações

Operações: salvar/editar/excluir produto; ajustar estoque (incremento/decremento seguro)

Adapter: ListAdapter + DiffUtil, thumb local com fallback ic_placeholder

6.4 Vender (Nova Venda)

Selecionar Produto do Estoque (diálogo filtrável; apenas quantidade > 0)

Preenche nome, mostra valor estimado e sugere valor unitário (revenda → estimado → 0)

Cálculo automático do total (TextWatcher)

Salvar: cria SaleEntity, SaleRepository.insert e baixa estoque via ProductRepository.decreaseStock

Proteções: sem movimentação antes do clique em “Salvar”; validação de quantidade ≤ estoque

6.5 Relatórios

RelatóriosActivity (hub): resumo + botões

FinanceiroActivity: período (DatePicker), Total de Vendas, Custo Total, Lucro Bruto, Ticket Médio

VendasActivity: período, nº de vendas, itens vendidos, valor total, ticket médio (base pronta; lista/adapter em roadmap)

7) Manipulação de imagens

Seleção pela galeria (ActivityResultContracts)

Salvas no filesDir com nomes únicos (ex.: produto_<timestamp>.jpg)

Persistimos apenas o caminho na entidade

Exibição com BitmapFactory.decodeFile, fallback para R.drawable.ic_placeholder

8) Decisões e correções importantes

“no such table: products / no such column: data” → padronizamos tabelas/colunas (product/purchase/sale, dataCompra, dataVenda)

TypeConverters(Date) → @TypeConverters(Converters::class)

Long vs Int → padronizado (id: Long)

TextWatcher → implementação correta para evitar Argument type mismatch

KAPT/Room Compiler → kotlin-kapt + room-compiler

Instanciação de Repositórios → sempre via DatabaseProvider.get(this) e DAOs, nunca Context nos repos

WAL ativado para desempenho

FallbackToDestructiveMigration durante dev para evitar schemas quebrados

9) Como rodar (ambiente & build)

JAVA_HOME (Temurin JDK 25):
C:\Program Files\Eclipse Adoptium\jdk-25.0.0.36-hotspot\bin\java.exe

PowerShell (exemplo):

setx JAVA_HOME "C:\Program Files\Eclipse Adoptium\jdk-25.0.0.36-hotspot" /M
setx PATH "%JAVA_HOME%\bin;%PATH%" /M
java -version


Gradle (na raiz do projeto):

C:\xampp\htdocs\COISABOA\android_COISABOA> .\gradlew clean build


Instalação do APK:

app/build/outputs/apk/debug/app-debug.apk

10) Testes funcionais (checklist rápido)

Comprar: inserir um produto novo com imagem → conferir estoque ↑

Comprar: comprar novamente o mesmo produto → conferir quantidade soma e valor estimado/revenda atualizam

Vender: selecionar produto com estoque > 0, vender quantidade válida → estoque ↓

Vender: tentar vender acima do estoque → deve bloquear

Relatórios: selecionar período atual → exibir valores coerentes

Gerenciar: editar produto (nome/valores/imagem) e excluir

11) Limitações conhecidas (MVP)

Migrations não implementadas (usa fallback destrutivo em dev)

Relatório de Vendas: lista/adapter detalhados em andamento (totais prontos)

Backup/sync: baseline preparado; implementação final configurável por ambiente

Sem gráficos por enquanto (somente totais numéricos)

12) Roadmap (próximas versões)

Relatório de Vendas completo: RecyclerView, filtros por produto/forma de pagamento, exportação CSV/PDF

Relatório de Estoque: valorização por produto, alerta de baixo estoque

Gráficos (Financeiro/Vendas): MPAndroidChart

Backup/sync robusto: fila offline + reconciliação de conflitos

Migrations para preservar dados entre versões

Compressão e limpeza de imagens órfãs

Tela oculta de logs (auditoria: compras/vendas/ajustes)

Segurança: criptografia do banco (SQLCipher) opcional

13) Git & versionamento

Repositório Git organizado; padrão de commit:

feat: nova venda com seleção de produto
fix: alinhamento de colunas no DAO
refactor: padroniza repositórios sem Context


Release v1.0.0 (MVP):

Compras, Vendas, Estoque, Relatórios (financeiro), DB local Room, seletores de data, imagens locais, UX estável.

14) Mensagem de release (sugestão)

COISABOA v1.0.0 — MVP funcional

✅ Fluxos: Comprar, Vender, Gerenciar Estoque, Relatórios

💾 Room + WAL + Converters(Date)

🧩 Repositórios padronizados (DAO-first)

🖼️ Imagens locais persistidas

🧮 Totais e indicadores financeiros por período

🧱 Base sólida para backup/sync em nuvem
