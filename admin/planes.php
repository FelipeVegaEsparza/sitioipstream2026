<?php
require_once 'auth.php';
include 'header.php';

$pdo = getDatabase();

// Fetch all plans with category information
$plans_stmt = $pdo->query("
    SELECT p.*, c.name as category_name, c.icon as category_icon, c.slug as category_slug
    FROM plans p
    LEFT JOIN plan_categories c ON p.category_id = c.id
    ORDER BY c.display_order ASC, p.id ASC
");
$plans = $plans_stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate statistics
$total_plans = count($plans);
$active_plans = count(array_filter($plans, fn($p) => $p['is_active']));
$inactive_plans = $total_plans - $active_plans;

// Calculate total revenue potential (monthly)
$monthly_revenue_potential = 0;
$annual_revenue_potential = 0;
foreach ($plans as $plan) {
    if ($plan['is_active']) {
        if (strpos($plan['plan_key'], 'monthly') !== false) {
            $monthly_revenue_potential += $plan['price'];
        } else {
            $annual_revenue_potential += $plan['price'];
        }
    }
}
?>

<!-- Page Header -->
<div class="mb-8">
    <?php if (isset($_SESSION['success'])): ?>
        <div class="mb-6 bg-green-50 border-l-4 border-green-400 p-4 rounded-lg">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-green-700"><?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></p>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="mb-6 bg-red-50 border-l-4 border-red-400 p-4 rounded-lg">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-red-700"><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></p>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-3xl font-bold text-gray-900 mb-2">Gestión de Planes</h2>
            <p class="text-gray-600">Administra los planes, precios y características de tu plataforma</p>
        </div>
        <button onclick="window.location.href='plan_crear.php'" class="bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white px-6 py-3 rounded-xl font-semibold shadow-lg transform hover:scale-105 transition-all duration-300 flex items-center">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
            </svg>
            Crear Nuevo Plan
        </button>
    </div>
</div>

<!-- Statistics Cards -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
    <!-- Total Plans -->
    <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl shadow-lg p-6 text-white transform hover:scale-105 transition-all duration-300">
        <div class="flex items-center justify-between mb-4">
            <div class="p-3 bg-white bg-opacity-20 rounded-xl">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                </svg>
            </div>
        </div>
        <h3 class="text-3xl font-bold mb-1"><?php echo $total_plans; ?></h3>
        <p class="text-blue-100 text-sm">Total de Planes</p>
    </div>

    <!-- Active Plans -->
    <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-2xl shadow-lg p-6 text-white transform hover:scale-105 transition-all duration-300">
        <div class="flex items-center justify-between mb-4">
            <div class="p-3 bg-white bg-opacity-20 rounded-xl">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
        </div>
        <h3 class="text-3xl font-bold mb-1"><?php echo $active_plans; ?></h3>
        <p class="text-green-100 text-sm">Planes Activos</p>
    </div>

    <!-- Inactive Plans -->
    <div class="bg-gradient-to-br from-gray-500 to-gray-600 rounded-2xl shadow-lg p-6 text-white transform hover:scale-105 transition-all duration-300">
        <div class="flex items-center justify-between mb-4">
            <div class="p-3 bg-white bg-opacity-20 rounded-xl">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                </svg>
            </div>
        </div>
        <h3 class="text-3xl font-bold mb-1"><?php echo $inactive_plans; ?></h3>
        <p class="text-gray-100 text-sm">Planes Inactivos</p>
    </div>

    <!-- Revenue Potential -->
    <div class="bg-gradient-to-br from-purple-500 to-indigo-600 rounded-2xl shadow-lg p-6 text-white transform hover:scale-105 transition-all duration-300">
        <div class="flex items-center justify-between mb-4">
            <div class="p-3 bg-white bg-opacity-20 rounded-xl">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
        </div>
        <h3 class="text-2xl font-bold mb-1">$<?php echo number_format($monthly_revenue_potential, 0, ',', '.'); ?></h3>
        <p class="text-purple-100 text-sm">Potencial Mensual</p>
    </div>
</div>

<!-- Plans Table -->
<div class="bg-white rounded-2xl shadow-lg overflow-hidden">
    <div class="px-6 py-5 border-b border-gray-200 bg-gradient-to-r from-blue-50 to-purple-50">
        <h2 class="text-2xl font-bold text-gray-900 flex items-center">
            <svg class="w-6 h-6 mr-3 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
            </svg>
            Todos los Planes
        </h2>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-4 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider w-16">ID</th>
                    <th scope="col" class="px-4 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Categoría</th>
                    <th scope="col" class="px-4 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Nombre del Plan</th>
                    <th scope="col" class="px-4 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Clave</th>
                    <th scope="col" class="px-4 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Precio (CLP)</th>
                    <th scope="col" class="px-4 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Estado</th>
                    <th scope="col" class="px-4 py-4 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Acciones</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($plans as $plan): ?>
                    <tr class="hover:bg-gray-50 transition-colors duration-200">
                        <td class="px-4 py-4 whitespace-nowrap">
                            <div class="text-sm font-bold text-gray-900">#<?php echo $plan['id']; ?></div>
                        </td>
                        <td class="px-4 py-4 whitespace-nowrap">
                            <?php if ($plan['category_name']): ?>
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-gradient-to-r from-blue-100 to-purple-100 text-purple-800">
                                    <span class="mr-1"><?php echo htmlspecialchars($plan['category_icon'] ?: '📁'); ?></span>
                                    <?php echo htmlspecialchars($plan['category_name']); ?>
                                </span>
                            <?php else: ?>
                                <span class="text-xs text-gray-400">Sin categoría</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10 bg-gradient-to-br from-blue-400 to-purple-500 rounded-lg flex items-center justify-center">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <div class="text-sm font-semibold text-gray-900"><?php echo htmlspecialchars($plan['plan_name']); ?></div>
                                    <div class="text-xs text-gray-500">
                                        <?php 
                                        echo strpos($plan['plan_key'], 'monthly') !== false ? '📅 Mensual' : '📆 Anual';
                                        ?>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-4 whitespace-nowrap">
                            <code class="text-xs bg-gray-100 px-2 py-1 rounded text-gray-700"><?php echo htmlspecialchars($plan['plan_key']); ?></code>
                        </td>
                        <td class="px-4 py-4 whitespace-nowrap">
                            <div class="text-base font-bold text-green-600">$<?php echo number_format($plan['price'], 0, ',', '.'); ?></div>
                            <div class="text-xs text-gray-500">CLP</div>
                        </td>
                        <td class="px-4 py-4 whitespace-nowrap">
                            <?php if ($plan['is_active']): ?>
                                <span class="px-2.5 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    Activo
                                </span>
                            <?php else: ?>
                                <span class="px-2.5 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                    </svg>
                                    Inactivo
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-4 whitespace-nowrap text-right text-sm font-medium space-x-1">
                            <a href="edit_plan.php?id=<?php echo $plan['id']; ?>" class="inline-flex items-center px-2.5 py-1.5 bg-indigo-100 hover:bg-indigo-200 text-indigo-700 rounded-lg transition-colors duration-200 text-xs">
                                <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                                Editar
                            </a>
                            <?php if ($plan['is_active']): ?>
                                <button onclick="togglePlanStatus(<?php echo $plan['id']; ?>, 0)" class="inline-flex items-center px-2.5 py-1.5 bg-red-100 hover:bg-red-200 text-red-700 rounded-lg transition-colors duration-200 text-xs">
                                    <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                    </svg>
                                    Desactivar
                                </button>
                            <?php else: ?>
                                <button onclick="togglePlanStatus(<?php echo $plan['id']; ?>, 1)" class="inline-flex items-center px-2.5 py-1.5 bg-green-100 hover:bg-green-200 text-green-700 rounded-lg transition-colors duration-200 text-xs">
                                    <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    Activar
                                </button>
                            <?php endif; ?>
                            <button onclick="deletePlan(<?php echo $plan['id']; ?>, '<?php echo htmlspecialchars($plan['plan_name'], ENT_QUOTES); ?>')" class="inline-flex items-center px-2.5 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition-colors duration-200 text-xs">
                                <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                                Eliminar
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function deletePlan(planId, planName) {
    if (confirm(`¿Estás seguro de que deseas eliminar el plan "${planName}"?\n\nEsta acción no se puede deshacer.`)) {
        window.location.href = `plan_eliminar.php?id=${planId}`;
    }
}

function togglePlanStatus(planId, newStatus) {
    if (confirm('¿Estás seguro de que quieres cambiar el estado de este plan?')) {
        fetch('toggle_plan_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `plan_id=${planId}&status=${newStatus}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error al cambiar el estado del plan');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al cambiar el estado del plan');
        });
    }
}
</script>

<?php include 'footer.php'; ?>
