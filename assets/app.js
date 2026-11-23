// Confirma acciones irreversibles y actualiza el total estimado del pedido.
document.querySelectorAll('[data-confirm]').forEach(form => form.addEventListener('submit', event => {
  if (!confirm(form.dataset.confirm)) event.preventDefault();
}));
const quantityInputs = document.querySelectorAll('[data-qty]');
function updateTotal() {
  const sum = [...quantityInputs].reduce((acc, input) => acc + Number(input.value || 0) * Number(input.dataset.price), 0);
  const target = document.querySelector('#total-estimado');
  if (target) target.textContent = new Intl.NumberFormat('en-US', {style:'currency',currency:'USD'}).format(sum);
}
quantityInputs.forEach(input => input.addEventListener('input', updateTotal));
updateTotal();

