/**
 * LessonForge Drag-and-Drop Lesson Builder
 * 
 * Enhanced block reordering with smooth drag animations
 */

const DragDrop = {
    draggedElement: null,
    dropZone: null,

    /**
     * Initialize drag and drop for lesson builder
     */
    init() {
        this.setupDragListeners();
        console.log('🔀 Drag-and-drop initialized');
    },

    /**
     * Setup drag event listeners
     */
    setupDragListeners() {
        document.addEventListener('dragstart', (e) => this.handleDragStart(e));
        document.addEventListener('dragend', (e) => this.handleDragEnd(e));
        document.addEventListener('dragover', (e) => this.handleDragOver(e));
        document.addEventListener('drop', (e) => this.handleDrop(e));
        document.addEventListener('dragenter', (e) => this.handleDragEnter(e));
        document.addEventListener('dragleave', (e) => this.handleDragLeave(e));
    },

    /**
     * Handle drag start
     */
    handleDragStart(e) {
        const block = e.target.closest('.builder-block');
        if (!block) return;

        this.draggedElement = block;
        block.classList.add('dragging');

        // Set drag image
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', block.dataset.blockId);

        // Add drop zones
        setTimeout(() => this.showDropZones(), 0);
    },

    /**
     * Handle drag end
     */
    handleDragEnd(e) {
        if (this.draggedElement) {
            this.draggedElement.classList.remove('dragging');
            this.draggedElement = null;
        }
        this.hideDropZones();
        this.removeAllHighlights();
    },

    /**
     * Handle drag over
     */
    handleDragOver(e) {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';

        const dropTarget = e.target.closest('.builder-block, .drop-zone');
        if (dropTarget && dropTarget !== this.draggedElement) {
            this.clearDropHighlights();
            dropTarget.classList.add('drop-highlight');
        }
    },

    /**
     * Handle drag enter
     */
    handleDragEnter(e) {
        e.preventDefault();
        const dropTarget = e.target.closest('.builder-block, .drop-zone');
        if (dropTarget && dropTarget !== this.draggedElement) {
            dropTarget.classList.add('drop-target');
        }
    },

    /**
     * Handle drag leave
     */
    handleDragLeave(e) {
        const dropTarget = e.target.closest('.builder-block, .drop-zone');
        if (dropTarget) {
            dropTarget.classList.remove('drop-target');
        }
    },

    /**
     * Handle drop
     */
    handleDrop(e) {
        e.preventDefault();

        const dropTarget = e.target.closest('.builder-block, .drop-zone');
        if (!dropTarget || !this.draggedElement || dropTarget === this.draggedElement) {
            return;
        }

        const container = document.getElementById('blocks-list');
        if (!container) return;

        const blockId = e.dataTransfer.getData('text/plain');
        const blocks = [...container.querySelectorAll('.builder-block')];
        const draggedIndex = blocks.indexOf(this.draggedElement);
        let targetIndex = blocks.indexOf(dropTarget);

        if (dropTarget.classList.contains('drop-zone')) {
            // Dropped on a zone - insert at that position
            const zoneIndex = parseInt(dropTarget.dataset.index);
            this.reorderBlocks(draggedIndex, zoneIndex);
        } else if (targetIndex !== -1) {
            // Dropped on another block
            const rect = dropTarget.getBoundingClientRect();
            const midpoint = rect.top + rect.height / 2;
            const insertAfter = e.clientY > midpoint;

            if (insertAfter) targetIndex++;
            if (targetIndex > draggedIndex) targetIndex--;

            this.reorderBlocks(draggedIndex, targetIndex);
        }

        this.hideDropZones();
        this.removeAllHighlights();

        // Show success feedback
        if (typeof app !== 'undefined') {
            app.showToast('Block reordered', 'success');
        }
    },

    /**
     * Reorder blocks in the state
     */
    reorderBlocks(fromIndex, toIndex) {
        if (typeof state !== 'undefined' && state.builderBlocks) {
            const blocks = state.builderBlocks;
            const [removed] = blocks.splice(fromIndex, 1);
            blocks.splice(toIndex, 0, removed);

            if (typeof app !== 'undefined') {
                app.renderBuilderBlocks();
            }
        }
    },

    /**
     * Show drop zones between blocks
     */
    showDropZones() {
        const container = document.getElementById('blocks-list');
        if (!container) return;

        const blocks = container.querySelectorAll('.builder-block');

        blocks.forEach((block, index) => {
            const zone = document.createElement('div');
            zone.className = 'drop-zone';
            zone.dataset.index = index;
            zone.innerHTML = '<span>Drop here</span>';
            block.parentNode.insertBefore(zone, block);
        });

        // Add final drop zone
        const finalZone = document.createElement('div');
        finalZone.className = 'drop-zone';
        finalZone.dataset.index = blocks.length;
        finalZone.innerHTML = '<span>Drop here</span>';
        container.appendChild(finalZone);
    },

    /**
     * Hide all drop zones
     */
    hideDropZones() {
        document.querySelectorAll('.drop-zone').forEach(zone => zone.remove());
    },

    /**
     * Clear drop highlights
     */
    clearDropHighlights() {
        document.querySelectorAll('.drop-highlight').forEach(el => {
            el.classList.remove('drop-highlight');
        });
    },

    /**
     * Remove all drag/drop highlights
     */
    removeAllHighlights() {
        document.querySelectorAll('.drop-target, .drop-highlight, .dragging').forEach(el => {
            el.classList.remove('drop-target', 'drop-highlight', 'dragging');
        });
    },

    /**
     * Make a block draggable
     */
    makeBlockDraggable(blockElement) {
        blockElement.setAttribute('draggable', 'true');
        blockElement.classList.add('draggable-block');
    }
};

// Export for global access
window.DragDrop = DragDrop;
