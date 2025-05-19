 
const frm = document.querySelector("#frmRegistro");
 
 
 
 
document.addEventListener("DOMContentLoaded", function() {
 
 
    //submit usuarios
    frm.addEventListener("submit", function(e) {
        e.preventDefault();
        let data = new FormData(this);
        const url = base_url + "admin/registrar";
        const http = new XMLHttpRequest();
        http.open("POST", url, true);
        http.send(data);
        http.onreadystatechange = function() {
            if (this.readyState == 4 && this.status == 200) {
                console.log(this.responseText);
                const res = JSON.parse(this.responseText);
                if (res.icono == "success") {
                     
                    
                    this.timeout = setTimeout(function() {
                        window.location = base_url + 'admin';
                    }, 2000);
                }
                 Swal.fire("Aviso?", res.msg.toUpperCase(), res.icono);
            }
        }
    });
});

 
 

 

