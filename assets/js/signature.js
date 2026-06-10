// assets/js/signature.js - Signature Pad Class for Digital Signatures

class SignaturePad {
    constructor(canvas, hiddenInput) {
        this.canvas = canvas;
        this.hiddenInput = hiddenInput;
        this.ctx = canvas.getContext('2d');
        this.drawing = false;
        this.lastX = 0;
        this.lastY = 0;
        
        // Initial configuration
        this.setupContext();
        
        // Event Listeners for Mouse
        canvas.addEventListener('mousedown', (e) => this.startDrawing(e));
        canvas.addEventListener('mousemove', (e) => this.draw(e));
        canvas.addEventListener('mouseup', () => this.stopDrawing());
        canvas.addEventListener('mouseout', () => this.stopDrawing());
        
        // Event Listeners for Touch (Mobile)
        canvas.addEventListener('touchstart', (e) => this.startDrawingTouch(e), { passive: false });
        canvas.addEventListener('touchmove', (e) => this.drawTouch(e), { passive: false });
        canvas.addEventListener('touchend', () => this.stopDrawing(), { passive: false });
        
        // Watch for window resize to adjust canvas bounds
        window.addEventListener('resize', () => this.handleResize());
        
        // Trigger initial size
        this.handleResize();
    }
    
    setupContext() {
        this.ctx.strokeStyle = '#0d233a'; // Navy blue matching theme
        this.ctx.lineWidth = 3;
        this.ctx.lineCap = 'round';
        this.ctx.lineJoin = 'round';
    }
    
    handleResize() {
        // Cache current content
        const tempCanvas = document.createElement('canvas');
        tempCanvas.width = this.canvas.width;
        tempCanvas.height = this.canvas.height;
        const tempCtx = tempCanvas.getContext('2d');
        tempCtx.drawImage(this.canvas, 0, 0);
        
        // Resize canvas to client width/height
        const rect = this.canvas.getBoundingClientRect();
        this.canvas.width = rect.width;
        this.canvas.height = rect.height;
        
        // Reapply context settings
        this.setupContext();
        
        // Restore contents
        this.ctx.drawImage(tempCanvas, 0, 0, tempCanvas.width, tempCanvas.height, 0, 0, this.canvas.width, this.canvas.height);
    }
    
    startDrawing(e) {
        this.drawing = true;
        const pos = this.getMousePos(e);
        [this.lastX, this.lastY] = [pos.x, pos.y];
    }
    
    startDrawingTouch(e) {
        if (e.touches.length === 1) {
            this.drawing = true;
            const pos = this.getTouchPos(e);
            [this.lastX, this.lastY] = [pos.x, pos.y];
            e.preventDefault(); // Stop scrolling on touch drag
        }
    }
    
    draw(e) {
        if (!this.drawing) return;
        const pos = this.getMousePos(e);
        this.drawSegment(pos.x, pos.y);
    }
    
    drawTouch(e) {
        if (!this.drawing) return;
        const pos = this.getTouchPos(e);
        this.drawSegment(pos.x, pos.y);
        e.preventDefault(); // Stop scrolling on touch drag
    }
    
    drawSegment(x, y) {
        this.ctx.beginPath();
        this.ctx.moveTo(this.lastX, this.lastY);
        this.ctx.lineTo(x, y);
        this.ctx.stroke();
        [this.lastX, this.lastY] = [x, y];
        this.updateHiddenInput();
    }
    
    stopDrawing() {
        this.drawing = false;
    }
    
    getMousePos(e) {
        const rect = this.canvas.getBoundingClientRect();
        return {
            x: e.clientX - rect.left,
            y: e.clientY - rect.top
        };
    }
    
    getTouchPos(e) {
        const rect = this.canvas.getBoundingClientRect();
        return {
            x: e.touches[0].clientX - rect.left,
            y: e.touches[0].clientY - rect.top
        };
    }
    
    clear() {
        this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
        this.hiddenInput.value = '';
    }
    
    updateHiddenInput() {
        this.hiddenInput.value = this.canvas.toDataURL('image/png');
    }
}
// Export class
window.SignaturePad = SignaturePad;
