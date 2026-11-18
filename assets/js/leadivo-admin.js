// Ajustes del plugin: muestra la fila "Opciones del Select" solo si el mensaje es un select
document.addEventListener("DOMContentLoaded", function () {
    const fieldTypeSelect = document.querySelector('select[name="leadivo_message_field_type"]');
    const selectOptionsRow = document.getElementById("leadivo-select-options-row");

    if (!fieldTypeSelect || !selectOptionsRow) {
        return;
    }

    fieldTypeSelect.addEventListener("change", function () {
        selectOptionsRow.style.display = this.value === "select" ? "table-row" : "none";
    });
});
