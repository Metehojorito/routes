<div class="actividad-item px-6 py-4 hover:bg-gray-50 flex items-center" data-id="<?php echo $actividad['id']; ?>">
    <div class="drag-handle text-gray-400 hover:text-gray-600 mr-4">
        <span class="material-symbols-outlined">drag_indicator</span>
    </div>
    
    <div class="flex items-start space-x-4 flex-1">
        <div class="flex-shrink-0 w-12 h-12 bg-<?php echo $actividad['color_categoria'] == 'primary' ? 'blue' : 'orange'; ?>-100 rounded-lg flex items-center justify-center">
            <span class="material-symbols-outlined text-<?php echo $actividad['color_categoria'] == 'primary' ? 'blue' : 'orange'; ?>-600">
                <?php echo htmlspecialchars($actividad['icono']); ?>
            </span>
        </div>
        <div class="flex-1 min-w-0">
            <h3 class="text-base font-semibold text-gray-900"><?php echo htmlspecialchars($actividad['titulo']); ?></h3>
            <?php if ($actividad['descripcion']): ?>
            <p class="text-sm text-gray-600 mt-1"><?php echo htmlspecialchars($actividad['descripcion']); ?></p>
            <?php endif; ?>
            <div class="flex gap-3 mt-2 text-xs text-gray-500">
                <span class="px-2 py-0.5 bg-gray-100 text-gray-700 rounded-full font-medium orden-badge">
                    Orden: <?php echo $actividad['orden']; ?>
                </span>
                <?php if ($actividad['lat'] && $actividad['lng']): ?>
                <a href="https://www.google.com/maps/search/?api=1&query=<?php echo $actividad['lat']; ?>,<?php echo $actividad['lng']; ?>" 
                   target="_blank"
                   class="px-2 py-0.5 bg-blue-50 text-blue-700 rounded-full font-medium hover:bg-blue-100 transition"
                   title="Ver en Google Maps">
                    📍 Ubicación
                </a>
                <?php endif; ?>
                <?php if ($actividad['total_detalles'] > 0): ?>
                <span class="px-2 py-0.5 bg-green-100 text-green-800 rounded-full font-medium">
                    <?php echo $actividad['total_detalles']; ?> detalles
                </span>
                <?php endif; ?>
				<?php if (!$actividad['visible']): ?>
				<span class="px-2 py-0.5 bg-gray-100 text-gray-700 rounded-full font-medium text-xs">
					Oculto
				</span>
				<?php endif; ?>
            </div>
        </div>
    </div>
    <div class="flex items-center space-x-2 ml-4">
        <a href="actividad_form.php?id=<?php echo $actividad['id']; ?>" 
           class="text-indigo-600 hover:text-indigo-900 p-2 rounded hover:bg-indigo-50 transition"
           title="Editar">
            <span class="material-symbols-outlined">edit</span>
        </a>
        <a href="actividad_delete.php?id=<?php echo $actividad['id']; ?>" 
           onclick="return confirm('¿Eliminar esta actividad y sus detalles?')" 
           class="text-red-600 hover:text-red-900 p-2 rounded hover:bg-red-50 transition"
           title="Eliminar">
            <span class="material-symbols-outlined">delete</span>
        </a>
    </div>
</div>