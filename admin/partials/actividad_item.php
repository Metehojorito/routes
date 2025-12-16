<div class="actividad-item px-3 sm:px-6 py-3 sm:py-4 hover:bg-gray-50 flex flex-col sm:flex-row items-start gap-3" data-id="<?php echo $actividad['id']; ?>">
    <!-- Handle de arrastre -->
    <div class="drag-handle text-gray-400 hover:text-gray-600 self-start sm:self-center">
        <span class="material-symbols-outlined text-xl sm:text-2xl">drag_indicator</span>
    </div>
    
    <!-- Contenido principal -->
    <div class="flex items-start space-x-3 sm:space-x-4 flex-1 min-w-0 w-full">
        <!-- Icono -->
        <div class="flex-shrink-0 w-10 h-10 sm:w-12 sm:h-12 bg-<?php echo $actividad['color_categoria'] == 'primary' ? 'blue' : 'orange'; ?>-100 rounded-lg flex items-center justify-center">
            <span class="material-symbols-outlined text-lg sm:text-xl text-<?php echo $actividad['color_categoria'] == 'primary' ? 'blue' : 'orange'; ?>-600">
                <?php echo htmlspecialchars($actividad['icono']); ?>
            </span>
        </div>
        
        <!-- Info -->
        <div class="flex-1 min-w-0">
            <h3 class="text-sm sm:text-base font-semibold text-gray-900 break-words"><?php echo htmlspecialchars($actividad['titulo']); ?></h3>
            <?php if ($actividad['descripcion']): ?>
            <p class="text-xs sm:text-sm text-gray-600 mt-1 break-words"><?php echo htmlspecialchars($actividad['descripcion']); ?></p>
            <?php endif; ?>
            
            <!-- Badges -->
            <div class="flex flex-wrap gap-2 mt-2 text-xs">
                <span class="px-2 py-0.5 bg-gray-100 text-gray-700 rounded-full font-medium orden-badge whitespace-nowrap">
                    #<?php echo $actividad['orden']; ?>
                </span>
                <?php if ($actividad['lat'] && $actividad['lng']): ?>
                <a href="https://www.google.com/maps/search/?api=1&query=<?php echo $actividad['lat']; ?>,<?php echo $actividad['lng']; ?>" 
                   target="_blank"
                   onclick="event.stopPropagation();"
                   class="px-2 py-0.5 bg-blue-50 text-blue-700 rounded-full font-medium hover:bg-blue-100 transition whitespace-nowrap">
                    📍 Mapa
                </a>
                <?php endif; ?>
                <?php if ($actividad['total_detalles'] > 0): ?>
                <span class="px-2 py-0.5 bg-green-100 text-green-800 rounded-full font-medium whitespace-nowrap">
                    <?php echo $actividad['total_detalles']; ?> detalles
                </span>
                <?php endif; ?>
                <?php if (!$actividad['visible']): ?>
                <span class="px-2 py-0.5 bg-gray-100 text-gray-700 rounded-full font-medium whitespace-nowrap">
                    Oculto
                </span>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Acciones -->
    <div class="flex items-center space-x-1 sm:space-x-2 self-end sm:self-center ml-auto sm:ml-4">
        <a href="actividad_form.php?id=<?php echo $actividad['id']; ?>" 
           onclick="event.stopPropagation();"
           class="text-indigo-600 hover:text-indigo-900 p-1.5 sm:p-2 rounded hover:bg-indigo-50 transition"
           title="Editar">
            <span class="material-symbols-outlined text-lg sm:text-xl">edit</span>
        </a>
        <a href="actividad_delete.php?id=<?php echo $actividad['id']; ?>" 
           onclick="event.stopPropagation(); return confirm('¿Eliminar esta actividad y sus detalles?')" 
           class="text-red-600 hover:text-red-900 p-1.5 sm:p-2 rounded hover:bg-red-50 transition"
           title="Eliminar">
            <span class="material-symbols-outlined text-lg sm:text-xl">delete</span>
        </a>
    </div>
</div>