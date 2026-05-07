<template>
  <main class="page-shell">
    <section class="workspace">
      <header class="hero">
        <div class="hero-copy">
          <span class="eyebrow">Inventario boutique</span>
          <h1>Controle de estoque</h1>
          <p>Organize produtos, quantidades e valores num painel delicado e facil de consultar.</p>
        </div>

        <div class="status-card" :class="{ danger: mensagemErro }">
          <span class="status-dot"></span>
          <div>
            <strong>{{ mensagemErro ? 'Atencao' : 'Online' }}</strong>
            <span>{{ mensagemErro || 'Dados sincronizados' }}</span>
          </div>
        </div>
      </header>

      <section class="metrics" aria-label="Resumo do inventario">
        <article class="metric rose">
          <span>Produtos</span>
          <strong>{{ totalProdutos }}</strong>
        </article>
        <article class="metric plum">
          <span>Unidades</span>
          <strong>{{ totalUnidades }}</strong>
        </article>
        <article class="metric sage">
          <span>Valor total</span>
          <strong>{{ formatarMoeda(valorTotal) }}</strong>
        </article>
      </section>

      <form class="product-form" @submit.prevent="salvar">
        <div class="form-title">
          <div>
            <span class="eyebrow">{{ editando ? 'Produto selecionado' : 'Novo produto' }}</span>
            <h2>{{ editando ? 'Atualizar cadastro' : 'Cadastrar item' }}</h2>
          </div>

          <button v-if="editando" type="button" class="soft-button" @click="limparFormulario">
            Cancelar edicao
          </button>
        </div>

        <div class="form-layout">
          <fieldset class="field-group product-details">
            <legend>Dados do produto</legend>
            <label>
              Nome do produto
              <input v-model.trim="form.nome" required placeholder="Ex: Bolsa floral" />
            </label>
          </fieldset>

          <fieldset class="field-group stock-details">
            <legend>Estoque e preco</legend>
            <div class="inline-fields">
              <label>
                Quantidade
                <input v-model.number="form.quantidade" required min="0" type="number" placeholder="0" />
              </label>

              <label>
                Preco
                <input v-model.number="form.preco" required min="0" step="0.01" type="number" placeholder="0.00" />
              </label>
            </div>
          </fieldset>

          <div class="form-action">
            <button class="primary-button" type="submit" :disabled="carregando">
              {{ editando ? 'Salvar alteracoes' : 'Adicionar' }}
            </button>
          </div>
        </div>
      </form>

      <section class="inventory-panel">
        <div class="panel-heading">
          <div>
            <span class="eyebrow">Catalogo</span>
            <h2>Produtos em estoque</h2>
          </div>
          <span class="count-pill">{{ totalProdutos }} itens</span>
        </div>

        <div v-if="carregando && !produtos.length" class="empty-state">
          A carregar produtos...
        </div>

        <div v-else-if="!produtos.length" class="empty-state">
          Nenhum produto registado.
        </div>

        <div v-else class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>Produto</th>
                <th>Qtd.</th>
                <th>Preco</th>
                <th>Total</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="produto in produtos" :key="produto.id">
                <td>
                  <div class="product-name">{{ produto.nome }}</div>
                </td>
                <td>
                  <span class="quantity-badge">{{ produto.quantidade }}</span>
                </td>
                <td>{{ formatarMoeda(produto.preco) }}</td>
                <td>{{ formatarMoeda(Number(produto.quantidade) * Number(produto.preco)) }}</td>
                <td>
                  <div class="actions">
                    <button type="button" class="table-button edit" @click="editar(produto)">
                      Editar
                    </button>
                    <button type="button" class="table-button delete" @click="remover(produto.id)">
                      Eliminar
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>
    </section>
  </main>
</template>

<script setup>
import axios from 'axios'
import { computed, onMounted, ref } from 'vue'

const api = (import.meta.env.VITE_API_URL || 'http://localhost:8000').replace(/\/$/, '')

const produtos = ref([])
const editando = ref(false)
const carregando = ref(false)
const mensagemErro = ref('')

const form = ref({
  id: null,
  nome: '',
  quantidade: '',
  preco: ''
})

const totalProdutos = computed(() => produtos.value.length)

const totalUnidades = computed(() =>
  produtos.value.reduce((total, produto) => total + Number(produto.quantidade || 0), 0)
)

const valorTotal = computed(() =>
  produtos.value.reduce(
    (total, produto) => total + Number(produto.quantidade || 0) * Number(produto.preco || 0),
    0
  )
)

const formatarMoeda = (valor) =>
  new Intl.NumberFormat('pt-MZ', {
    style: 'currency',
    currency: 'MZN',
    maximumFractionDigits: 2
  }).format(Number(valor || 0))

const limparFormulario = () => {
  form.value = {
    id: null,
    nome: '',
    quantidade: '',
    preco: ''
  }
  editando.value = false
}

const carregar = async () => {
  carregando.value = true
  mensagemErro.value = ''

  try {
    const response = await axios.get(api)
    produtos.value = response.data
  } catch (error) {
    mensagemErro.value = 'Falha ao ligar ao servidor'
  } finally {
    carregando.value = false
  }
}

const salvar = async () => {
  carregando.value = true
  mensagemErro.value = ''

  try {
    if (editando.value) {
      await axios.put(api, form.value)
    } else {
      await axios.post(api, form.value)
    }

    limparFormulario()
    await carregar()
  } catch (error) {
    mensagemErro.value = 'Nao foi possivel guardar'
  } finally {
    carregando.value = false
  }
}

const editar = (produto) => {
  form.value = { ...produto }
  editando.value = true
}

const remover = async (id) => {
  carregando.value = true
  mensagemErro.value = ''

  try {
    await axios.delete(`${api}?id=${id}`)
    await carregar()
  } catch (error) {
    mensagemErro.value = 'Nao foi possivel eliminar'
  } finally {
    carregando.value = false
  }
}

onMounted(carregar)
</script>

<style>
:root {
  color: #352737;
  background: #fff7fb;
  font-family: "Trebuchet MS", "Avenir Next", Candara, sans-serif;
}

* {
  box-sizing: border-box;
}

body {
  margin: 0;
  min-height: 100vh;
  background:
    linear-gradient(135deg, rgba(246, 154, 191, 0.18), rgba(181, 153, 224, 0.16) 46%, rgba(150, 185, 166, 0.14)),
    #fff7fb;
}

button,
input {
  font: inherit;
}

button {
  border: 0;
}

.page-shell {
  min-height: 100vh;
  padding: 30px;
}

.workspace {
  width: min(1160px, 100%);
  margin: 0 auto;
}

.hero {
  display: grid;
  grid-template-columns: minmax(0, 1fr) 260px;
  gap: 20px;
  align-items: stretch;
  margin-bottom: 18px;
}

.hero-copy,
.status-card,
.metric,
.product-form,
.inventory-panel {
  border: 1px solid rgba(158, 93, 126, 0.16);
  border-radius: 8px;
  background: rgba(255, 255, 255, 0.82);
  box-shadow: 0 18px 48px rgba(133, 72, 108, 0.12);
}

.hero-copy {
  padding: 28px;
}

.hero-copy p {
  max-width: 640px;
  margin: 12px 0 0;
  color: #7b6274;
  font-size: 17px;
  line-height: 1.55;
}

.eyebrow {
  display: block;
  margin-bottom: 7px;
  color: #b34f7d;
  font-size: 12px;
  font-weight: 800;
  letter-spacing: 0;
  text-transform: uppercase;
}

h1,
h2 {
  margin: 0;
  color: #3a203e;
  letter-spacing: 0;
}

h1 {
  font-family: Georgia, "Times New Roman", serif;
  font-size: clamp(36px, 6vw, 62px);
  font-weight: 700;
  line-height: 0.98;
}

h2 {
  font-family: Georgia, "Times New Roman", serif;
  font-size: 25px;
  line-height: 1.1;
}

.status-card {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 22px;
}

.status-card strong,
.status-card span {
  display: block;
}

.status-card strong {
  margin-bottom: 3px;
  color: #4a2d4d;
}

.status-card span {
  color: #7b6274;
  font-size: 14px;
}

.status-dot {
  width: 12px;
  height: 12px;
  border-radius: 50%;
  background: #7aa68d;
  box-shadow: 0 0 0 6px rgba(122, 166, 141, 0.17);
}

.status-card.danger .status-dot {
  background: #d45172;
  box-shadow: 0 0 0 6px rgba(212, 81, 114, 0.16);
}

.metrics {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 14px;
  margin-bottom: 18px;
}

.metric {
  position: relative;
  overflow: hidden;
  min-height: 118px;
  padding: 20px;
}

.metric::before {
  position: absolute;
  inset: 0 auto 0 0;
  width: 6px;
  content: "";
}

.metric.rose::before {
  background: #df7aa2;
}

.metric.plum::before {
  background: #8e5aa6;
}

.metric.sage::before {
  background: #7aa68d;
}

.metric span {
  display: block;
  margin-bottom: 10px;
  color: #836779;
  font-size: 13px;
  font-weight: 800;
}

.metric strong {
  color: #3a203e;
  font-size: clamp(28px, 5vw, 38px);
  line-height: 1;
}

.product-form,
.inventory-panel {
  padding: 22px;
}

.product-form {
  margin-bottom: 18px;
}

.form-title,
.panel-heading {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
  margin-bottom: 18px;
}

.form-layout {
  display: grid;
  grid-template-columns: minmax(260px, 1.15fr) minmax(260px, 1fr) 150px;
  gap: 14px;
  align-items: stretch;
}

.field-group {
  min-width: 0;
  margin: 0;
  padding: 16px;
  border: 1px solid rgba(179, 79, 125, 0.18);
  border-radius: 8px;
  background: #fffafd;
}

legend {
  padding: 0 7px;
  color: #9f4d78;
  font-size: 13px;
  font-weight: 900;
}

label {
  display: grid;
  gap: 8px;
  color: #6d5367;
  font-size: 14px;
  font-weight: 800;
}

.inline-fields {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
}

input {
  width: 100%;
  min-height: 48px;
  padding: 0 14px;
  border: 1px solid rgba(154, 89, 124, 0.24);
  border-radius: 8px;
  background: #ffffff;
  color: #352737;
  outline: none;
  transition: border-color 160ms ease, box-shadow 160ms ease;
}

input:focus {
  border-color: #c9568d;
  box-shadow: 0 0 0 4px rgba(201, 86, 141, 0.14);
}

.form-action {
  display: flex;
  align-items: end;
}

.primary-button,
.soft-button,
.table-button {
  min-height: 43px;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 900;
  transition: transform 160ms ease, opacity 160ms ease, box-shadow 160ms ease;
}

.primary-button {
  width: 100%;
  background: #c9568d;
  color: #ffffff;
  box-shadow: 0 10px 24px rgba(201, 86, 141, 0.25);
}

.primary-button:hover,
.soft-button:hover,
.table-button:hover {
  transform: translateY(-1px);
}

.primary-button:disabled {
  cursor: wait;
  opacity: 0.72;
  transform: none;
}

.soft-button {
  padding: 0 13px;
  border: 1px solid rgba(179, 79, 125, 0.24);
  background: #fff5fa;
  color: #9f4d78;
}

.count-pill {
  display: inline-flex;
  align-items: center;
  min-height: 36px;
  padding: 0 13px;
  border-radius: 999px;
  background: #f6e9f5;
  color: #7d4d8f;
  font-size: 13px;
  font-weight: 900;
  white-space: nowrap;
}

.table-wrap {
  overflow-x: auto;
}

table {
  width: 100%;
  min-width: 690px;
  border-collapse: collapse;
}

th {
  padding: 0 12px 12px;
  color: #9b7d91;
  font-size: 12px;
  font-weight: 900;
  letter-spacing: 0;
  text-align: left;
  text-transform: uppercase;
}

td {
  padding: 15px 12px;
  border-top: 1px solid #f0dfe9;
  color: #5d475a;
  font-size: 14px;
  vertical-align: middle;
}

.product-name {
  color: #3a203e;
  font-weight: 900;
}

.quantity-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 42px;
  min-height: 30px;
  border-radius: 999px;
  background: #f7edf6;
  color: #7d4d8f;
  font-weight: 900;
}

.actions {
  display: flex;
  justify-content: flex-end;
  gap: 8px;
}

.table-button {
  padding: 0 11px;
  background: #f9eef4;
  color: #944d76;
}

.table-button.edit {
  background: #f5e7f7;
  color: #7d4d8f;
}

.table-button.delete {
  background: #fff0f2;
  color: #b43e62;
}

.empty-state {
  display: grid;
  min-height: 210px;
  place-items: center;
  border: 1px dashed rgba(179, 79, 125, 0.26);
  border-radius: 8px;
  background: #fffafd;
  color: #8a6f82;
  font-weight: 800;
  text-align: center;
}

@media (max-width: 940px) {
  .hero,
  .metrics,
  .form-layout {
    grid-template-columns: 1fr;
  }

  .form-action {
    align-items: stretch;
  }
}

@media (max-width: 640px) {
  .page-shell {
    padding: 18px;
  }

  .hero-copy,
  .status-card,
  .product-form,
  .inventory-panel,
  .metric {
    padding: 16px;
  }

  .form-title,
  .panel-heading {
    flex-direction: column;
  }

  .inline-fields {
    grid-template-columns: 1fr;
  }

  h1 {
    font-size: 38px;
  }
}
</style>
