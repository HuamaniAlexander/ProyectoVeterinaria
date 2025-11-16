fetch("https://petzone.byethost18.com/controlador/auth.php", {
  method: "POST",
  headers: {
    "Content-Type": "application/json"
  },
  body: JSON.stringify({
    username: "alexander",
    password: "123456"
  })
})
.then(res => res.json())
.then(data => console.log(data));
