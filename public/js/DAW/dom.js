// JS
const button = document.querySelector('button');

const parrafos = document.querySelectorAll('p');

button.addEventListener("click", () => {
    parrafos.forEach(p => {
        p.style.color = (p.style.color == "red") ? "blue" : "red";
    })
})
