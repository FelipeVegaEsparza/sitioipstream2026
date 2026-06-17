const v="/php/api";let d=[],m={},r=null;async function h(){try{const t=await(await fetch(`${v}/get-tutorials.php`)).json();if(t.success){d=(t.categories||[]).map(function(i){return{...i,id:parseInt(i.id)}});const n=t.tutorialsByCategory||{};m={};for(let i in n)m[parseInt(i)]=n[i];d.length>0?(p(),f(),x()):u("No hay tutoriales disponibles")}else u(t.error||"Error al cargar tutoriales")}catch(e){console.error("Error loading tutorials:",e),u("Error de conexión al cargar tutoriales")}}function p(){const e=document.getElementById("categories-tabs");e&&(e.innerHTML=d.map((t,n)=>`
            <button
                id="tab-${t.slug}"
                class="category-tab px-6 py-3 rounded-full font-medium transition-all duration-300 ${n===0?"active":""}"
                data-category-id="${t.id}"
            >
                ${t.name}
            </button>
        `).join(""),document.querySelectorAll(".category-tab").forEach(function(t){t.addEventListener("click",function(){const n=parseInt(t.getAttribute("data-category-id")||"0");b(n)})}),d.length>0&&(r=d[0].id))}function b(e){r=e,document.querySelectorAll(".category-tab").forEach(function(t){parseInt(t.getAttribute("data-category-id")||"0")===e?t.classList.add("active"):t.classList.remove("active")}),f()}function f(){const e=document.getElementById("tutorials-content");if(!e||r===null)return;const t=d.find(function(o){return o.id===r});if(!t)return;const n=m[r]||[],i={beginner:"Básico",intermediate:"Intermedio",advanced:"Avanzado"},s={beginner:"blue",intermediate:"yellow",advanced:"red"};e.innerHTML=`
            <div class="text-center mb-12">
                <div class="bg-${t.color}-100 rounded-full w-20 h-20 flex items-center justify-center mx-auto mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-${t.color}-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                    </svg>
                </div>
                <h3 class="text-2xl font-bold mb-2">${t.name}</h3>
            </div>

            ${n.length===0?`
                <div class="text-center py-8">
                    <p class="text-gray-600">No hay tutoriales en esta categoría.</p>
                </div>
            `:`
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    ${n.map(function(o){const a=y(o.video_url),c=i[o.difficulty]||"Básico",g=s[o.difficulty]||"blue";return`
                            <div class="bg-white rounded-xl shadow-lg overflow-hidden hover:shadow-xl transition-shadow duration-300">
                                <div class="relative">
                                    <img
                                        src="https://img.youtube.com/vi/${a}/maxresdefault.jpg"
                                        alt="${o.title}"
                                        class="w-full h-48 object-cover"
                                        onerror="this.src='https://img.youtube.com/vi/${a}/hqdefault.jpg'"
                                    />
                                    <div class="absolute inset-0 bg-black bg-opacity-40 flex items-center justify-center opacity-0 hover:opacity-100 transition-opacity duration-300">
                                        <button
                                            class="play-video bg-${t.color}-600 hover:bg-${t.color}-700 text-white rounded-full p-4 transform hover:scale-110 transition-transform duration-300"
                                            data-video-url="${o.video_url}"
                                            data-title="${o.title}"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </button>
                                    </div>
                                    ${o.duration?`
                                        <div class="absolute top-4 right-4 bg-${t.color}-600 text-white px-2 py-1 rounded text-sm font-medium">
                                            ${o.duration}
                                        </div>
                                    `:""}
                                </div>
                                <div class="p-6">
                                    <div class="flex items-center mb-3">
                                        <span class="bg-${g}-100 text-${g}-600 px-2 py-1 rounded-full text-xs font-medium">
                                            ${c}
                                        </span>
                                        ${o.duration?`
                                            <span class="ml-2 text-gray-500 text-sm">
                                                ${o.duration}
                                            </span>
                                        `:""}
                                    </div>
                                    <h3 class="text-xl font-bold mb-3">${o.title}</h3>
                                    <p class="text-gray-600 mb-4">
                                        ${o.description.length>120?o.description.substring(0,120)+"...":o.description}
                                    </p>
                                    <button
                                        class="play-video w-full bg-${t.color}-600 text-white py-2 px-4 rounded-lg hover:bg-${t.color}-700 transition-colors"
                                        data-video-url="${o.video_url}"
                                        data-title="${o.title}"
                                    >
                                        Ver Tutorial
                                    </button>
                                </div>
                            </div>
                        `}).join("")}
                </div>
            `}
        `,document.querySelectorAll(".play-video").forEach(function(o){o.addEventListener("click",function(){const a=o.getAttribute("data-video-url")||"",c=o.getAttribute("data-title")||"";w(a,c)})})}function y(e){if(!e)return"";const t=e.match(/embed\/([a-zA-Z0-9_-]+)/);if(t)return t[1];const n=e.match(/[?&]v=([a-zA-Z0-9_-]+)/);if(n)return n[1];const i=e.match(/youtu\.be\/([a-zA-Z0-9_-]+)/);return i?i[1]:""}function w(e,t){const n=document.getElementById("video-modal"),i=document.getElementById("youtube-iframe"),s=document.getElementById("modal-title");if(!n||!i||!s)return;const o=y(e);o&&(s.textContent=t,i.src=`https://www.youtube.com/embed/${o}?autoplay=1`,n.classList.remove("hidden"),document.body.style.overflow="hidden")}function l(){const e=document.getElementById("video-modal"),t=document.getElementById("youtube-iframe");!e||!t||(e.classList.add("hidden"),t.src="",document.body.style.overflow="auto")}function x(){const e=document.getElementById("loading-state"),t=document.getElementById("error-state"),n=document.getElementById("categories-section"),i=document.getElementById("tutorials-section");e&&e.classList.add("hidden"),t&&t.classList.add("hidden"),n&&n.classList.remove("hidden"),i&&i.classList.remove("hidden")}function u(e){const t=document.getElementById("loading-state"),n=document.getElementById("categories-section"),i=document.getElementById("tutorials-section"),s=document.getElementById("error-state");if(t&&t.classList.add("hidden"),n&&n.classList.add("hidden"),i&&i.classList.add("hidden"),s){s.classList.remove("hidden");const o=s.querySelector("p");o&&(o.textContent=e)}}document.addEventListener("DOMContentLoaded",function(){h();const e=document.getElementById("close-modal"),t=document.getElementById("video-modal");e&&e.addEventListener("click",l),t&&t.addEventListener("click",function(n){n.target===t&&l()}),document.addEventListener("keydown",function(n){n.key==="Escape"&&t&&!t.classList.contains("hidden")&&l()})});
