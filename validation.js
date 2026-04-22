function validateMedicineForm() {

  let name = document.getElementById("medicineName").value.trim();
  let qty = document.getElementById("medicineQty").value.trim();
  let date = document.getElementById("medicineDate").value;

  let nameError = document.getElementById("nameError");
  let qtyError = document.getElementById("qtyError");
  let dateError = document.getElementById("dateError");

  nameError.textContent = "";
  qtyError.textContent = "";
  dateError.textContent = "";

  let valid = true;

  // NAME
if (name === "") {
  nameError.textContent = "Medicine name is required";
  valid = false;
}
else if (/^\d+$/.test(name)) {
  nameError.textContent = "Name cannot be only numbers";
  valid = false;
}
else if (!/[a-zA-Z\u0600-\u06FF]/.test(name)) {
  nameError.textContent = "Name must contain at least one letter";
  valid = false;
}

  // QTY
  if (qty === "") {
    qtyError.textContent = "Quantity is required";
    valid = false;
  } else if (isNaN(qty) || parseInt(qty) <= 0) {
    qtyError.textContent = "Quantity must be a number > 0";
    valid = false;
  }

  // DATE
  if (date === "") {
    dateError.textContent = "Expiry date is required";
    valid = false;
  } else {
    let today = new Date();
    today.setHours(0,0,0,0);

    let selectedDate = new Date(date);

    if (selectedDate < today) {
      dateError.textContent = "Expiry date must be in future";
      valid = false;
    }
  }

  return valid; 
}