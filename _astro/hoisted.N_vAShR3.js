import"./Layout.astro_astro_type_script_index_0_lang.C-6M4QD5.js";document.addEventListener("DOMContentLoaded",()=>{const i=()=>{const s=document.getElementById("local-subscriptions-container"),t=document.getElementById("no-local-subs");if(s){for(;s.firstChild&&s.firstChild!==t;)s.removeChild(s.firstChild);const o=localStorage.getItem("lastSubscription");if(o)try{const e=JSON.parse(o);t.classList.add("hidden");const n=document.createElement("div");n.className="bg-gray-50 p-4 rounded-lg mb-4",n.innerHTML=`
              <div class="flex justify-between items-center mb-2">
                <h3 class="font-medium text-gray-900">Suscripción: ${e.firstName} ${e.lastName}</h3>
                <span class="text-sm text-gray-500">${new Date(e.timestamp).toLocaleString()}</span>
              </div>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-2 text-sm">
                <div><span class="font-medium">Plan:</span> ${e.plan==="radio"?"Radio Online":"Radio + TV Online"}</div>
                <div><span class="font-medium">Email:</span> ${e.email}</div>
                <div><span class="font-medium">WhatsApp:</span> ${e.whatsapp}</div>
                <div><span class="font-medium">Proyecto:</span> ${e.projectName}</div>
              </div>
            `,s.insertBefore(n,t)}catch(e){console.error("Error al cargar suscripción local:",e)}else t.classList.remove("hidden")}};i();const a=document.getElementById("refresh-btn");a&&a.addEventListener("click",()=>{i()})});
