document.addEventListener("DOMContentLoaded", () => {
  const titulo = document.getElementById("titulo-productos");
  const linea = document.createElement("div");

  linea.style.position = "absolute";
  linea.style.bottom = "0";
  linea.style.left = "50%";
  linea.style.transform = "translateX(-50%)";
  linea.style.height = "3px";
  linea.style.backgroundColor = "#23906F";
  linea.style.borderRadius = "2px";
  linea.style.width = "0";
  linea.style.transition = "width 0.6s ease-in-out";
  titulo.style.position = "relative";
  titulo.appendChild(linea);

  function animarLinea() {
    linea.style.width = "300px";
    setTimeout(() => {
      linea.style.width = "0";
    }, 1000);
  }

  setInterval(animarLinea, 2000);
});
