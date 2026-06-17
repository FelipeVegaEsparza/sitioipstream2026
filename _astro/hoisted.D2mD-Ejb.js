const g=l=>new Intl.NumberFormat("es-CL",{style:"currency",currency:"CLP"}).format(Math.round(l)),p=(l,n)=>{const a=n/12,u=l*12-n;return{annualMonthlyEquivalent:a,saving:u}};async function f(){try{const n=await(await fetch("/php/api/get-plans.php")).json();if(!n.success)throw new Error(n.error||"Error al cargar los planes");document.getElementById("loading-state")?.classList.add("hidden"),document.getElementById("plans-content")?.classList.remove("hidden"),h(n.categories),b(n.plans,n.categories),v()}catch(l){console.error("Error loading plans:",l),document.getElementById("loading-state")?.classList.add("hidden"),document.getElementById("error-state")?.classList.remove("hidden")}}function h(l){const n=document.getElementById("category-filters");n&&(n.innerHTML=l.map((a,u)=>`
      <button 
        data-category="${a.id}" 
        class="category-filter px-6 py-2 rounded-full text-sm font-medium transition-all duration-300 ${u===0?"active":""}"
      >
        <span class="mr-2">${a.icon}</span>
        ${a.name}
      </button>
    `).join(""))}function b(l,n){const a=document.getElementById("plans-grid");if(!a)return;const u={};n.forEach(e=>{u[e.id]=l.filter(s=>s.category_id===e.id)});const i=l.map(e=>{const s=n.find(m=>m.id===e.category_id);if(!s)return"";const o=e.monthly_price||e.price||0,d=e.annual_price||0,c=o&&d?p(o,d):null,t=s.slug.includes("tv")||s.slug.includes("radio-tv"),x=e.features||[];return`
        <div class="animate-fade-in plan-card h-full flex flex-col" data-category="${s.id}">
          <div class="${t?"relative":""} h-full flex flex-col">
            <div class="rounded-3xl overflow-hidden shadow-xl transition-all duration-500 hover:shadow-2xl hover:-translate-y-2 h-full flex flex-col ${t?"bg-gradient-to-br from-blue-600 to-indigo-700":"glass-card"}">
              ${e.image_url?`
                <div class="w-full">
                  <img 
                    src="${e.image_url}" 
                    alt="${e.title||e.plan_name}" 
                    class="w-full h-48 object-contain ${t?"bg-white/10":"bg-gradient-to-br from-blue-50 to-purple-50"}" 
                  />
                </div>
              `:e.icon||s.icon?`
                <div class="w-full h-32 flex items-center justify-center ${t?"bg-white/10":"bg-gradient-to-br from-blue-50 to-purple-50"}">
                  <div class="w-20 h-20 rounded-full flex items-center justify-center text-4xl ${t?"bg-white/20":"bg-blue-100"}">
                    ${e.icon||s.icon}
                  </div>
                </div>
              `:""}
              
              <div class="p-8 md:p-10 flex-1 flex flex-col">
                <h3 class="text-2xl md:text-3xl font-bold ${t?"text-white":""}">
                  ${e.title||e.plan_name}
                </h3>
                <p class="my-8 text-lg ${t?"text-blue-100":"text-gray-600"}">
                  ${e.description||s.description}
                </p>
                
                <div class="space-y-4 mb-8 flex-1">
                  ${x.length>0?x.map(m=>`
                    <div class="flex items-center">
                      <div class="flex-shrink-0 w-6 h-6 rounded-full flex items-center justify-center ${t?"bg-white bg-opacity-20 text-white":"bg-blue-100 text-blue-600"}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                          <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                        </svg>
                      </div>
                      <span class="ml-3 ${t?"text-white":"text-gray-700"}">${m}</span>
                    </div>
                  `).join(""):`
                    <p class="text-sm ${t?"text-white/70":"text-gray-500"}">
                      No hay características disponibles
                    </p>
                  `}
                </div>
                
                <div class="mb-8">
                  ${e.monthly_price&&e.annual_price?`
                    <div>
                      <div class="flex items-center justify-center mb-4">
                        <div class="rounded-full p-1 inline-flex ${t?"bg-white/20":"bg-gray-100"}">
                          <button 
                            class="billing-toggle-btn px-4 py-1 rounded-full text-xs font-medium transition-all duration-300 active-billing ${t?"text-white":""}"
                            data-billing="monthly"
                            data-plan-id="${e.id}"
                          >
                            Mensual
                          </button>
                          <button 
                            class="billing-toggle-btn px-4 py-1 rounded-full text-xs font-medium transition-all duration-300 ${t?"text-white":""}"
                            data-billing="annual"
                            data-plan-id="${e.id}"
                          >
                            Anual
                            <span class="ml-1 px-2 py-0.5 rounded-full text-xs font-bold ${t?"bg-green-400 text-green-900":"bg-green-100 text-green-600"}">
                              ${Math.round((1-d/12/o)*100)}% OFF
                            </span>
                          </button>
                        </div>
                      </div>

                      <div class="price-display price-monthly-${e.id}">
                        <div class="flex items-end justify-center">
                          <span class="text-5xl font-bold ${t?"text-white":"text-blue-600"}">
                            ${g(o)}
                          </span>
                          <span class="ml-2 mb-1 ${t?"text-blue-200":"text-gray-500"}">/mes</span>
                        </div>
                        <p class="text-sm mt-1 text-center ${t?"text-blue-200":"text-gray-500"}">
                          ${e.billing_note||"IVA Incluido - Facturación mensual"}
                        </p>
                      </div>

                      <div class="price-display price-annual-${e.id} hidden">
                        <div class="flex items-end justify-center">
                          <span class="text-5xl font-bold ${t?"text-white":"text-blue-600"}">
                            ${g(d)}
                          </span>
                          <span class="ml-2 mb-1 ${t?"text-blue-200":"text-gray-500"}">/año</span>
                        </div>
                        <p class="text-sm mt-1 text-center ${t?"text-blue-200":"text-gray-500"}">
                          ${e.billing_note||"IVA Incluido - Pago anual"}
                        </p>
                        ${c?`
                          <div class="mt-2 text-center">
                            <p class="text-sm ${t?"text-blue-200":"text-gray-500"}">
                              Equivale a ${g(c.annualMonthlyEquivalent)}/mes
                            </p>
                            <p class="text-sm font-medium ${t?"text-green-300":"text-green-600"}">
                              ¡Ahorras ${g(c.saving)} al año!
                            </p>
                          </div>
                        `:""}
                      </div>
                    </div>
                  `:`
                    <div>
                      <div class="flex items-end justify-center">
                        <span class="text-5xl font-bold ${t?"text-white":"text-blue-600"}">
                          ${g(o)}
                        </span>
                        <span class="ml-2 mb-1 ${t?"text-blue-200":"text-gray-500"}">
                          ${e.plan_key?.includes("annual")?"/año":"/mes"}
                        </span>
                      </div>
                      <p class="text-sm mt-1 text-center ${t?"text-blue-200":"text-gray-500"}">
                        ${e.billing_note||"IVA Incluido"}
                      </p>
                    </div>
                  `}
                </div>

                <div class="space-y-3">
                  <a 
                    href="/checkout?plan=${e.plan_key}&billing=monthly"
                    class="checkout-link-${e.id} w-full text-center inline-block ${t?"btn-primary-white":"btn-primary"}"
                    data-plan-key="${e.plan_key}"
                    data-plan-id="${e.id}"
                  >
                    <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Contratar Ahora
                  </a>
                  ${e.demo_url?`
                    <a 
                      href="${e.demo_url}" 
                      target="_blank" 
                      rel="noopener noreferrer" 
                      class="w-full text-center inline-block ${t?"btn-secondary-white":"btn-secondary"}"
                    >
                      <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                      </svg>
                      Ver Ejemplo
                    </a>
                  `:""}
                </div>
              </div>
            </div>
          </div>
        </div>
      `}).join(""),r=`
      <div class="animate-fade-in h-full flex flex-col">
        <div class="h-full flex flex-col bg-gradient-to-br from-indigo-50 via-purple-50 to-pink-50 rounded-3xl p-8 border border-purple-200 shadow-lg">
          <div class="mb-6">
            <h3 class="text-3xl font-bold mb-3 text-transparent bg-clip-text bg-gradient-to-r from-indigo-600 to-purple-600">
              🏠 Hostreams
            </h3>
            <p class="text-lg font-semibold text-gray-800 mb-2">
              El directorio de radios y canales de TV independientes
            </p>
            <p class="text-gray-600 leading-relaxed">
              Descubre, escucha y mira emisoras independientes de todo el mundo en un solo lugar.
            </p>
          </div>
          <div class="mb-6">
            <a href="https://hostreams.com" target="_blank" rel="noopener noreferrer" class="block w-full text-center px-6 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white rounded-xl font-semibold transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-1">
              👉 Explorar radios y canales
            </a>
          </div>
        </div>
      </div>
    `;a.innerHTML=i+r}function v(){const l=document.querySelectorAll(".category-filter"),n=document.querySelectorAll(".plan-card");function a(i){n.forEach(r=>{const e=r.getAttribute("data-category"),s=r;e===i?s.style.display="block":s.style.display="none"})}if(l.forEach(i=>{i.addEventListener("click",()=>{const r=i.getAttribute("data-category");r&&(l.forEach(e=>e.classList.remove("active")),i.classList.add("active"),a(r))})}),l.length>0){const i=l[0].getAttribute("data-category");i&&a(i)}document.querySelectorAll(".billing-toggle-btn").forEach(i=>{i.addEventListener("click",()=>{const r=i.getAttribute("data-billing"),e=i.getAttribute("data-plan-id");document.querySelectorAll(`[data-plan-id="${e}"]`).forEach(t=>t.classList.remove("active-billing")),i.classList.add("active-billing");const o=document.querySelector(`.price-monthly-${e}`),d=document.querySelector(`.price-annual-${e}`);r==="monthly"?(o?.classList.remove("hidden"),d?.classList.add("hidden")):(o?.classList.add("hidden"),d?.classList.remove("hidden"));const c=document.querySelector(`.checkout-link-${e}`);if(c){const t=c.getAttribute("data-plan-key");c.href=`/checkout?plan=${t}&billing=${r}`}})})}f();
