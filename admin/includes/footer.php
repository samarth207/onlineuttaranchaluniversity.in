    </div><!-- /.admin-content -->
</div><!-- /.admin-main -->

<!-- Toast Notification Container -->
<div class="toast-container" id="toastContainer"></div>

<!-- Confirm Modal -->
<div class="modal-backdrop" id="confirmBackdrop" style="display:none">
    <div class="confirm-modal">
        <div class="confirm-icon"><i class="fas fa-exclamation-triangle"></i></div>
        <h3 class="confirm-title" id="confirmTitle">Are you sure?</h3>
        <p class="confirm-msg" id="confirmMsg">This action cannot be undone.</p>
        <div class="confirm-actions">
            <button class="btn-confirm-cancel" onclick="closeConfirm()">Cancel</button>
            <button class="btn-confirm-ok" id="confirmOkBtn">Confirm</button>
        </div>
    </div>
</div>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script src="/admin/assets/admin.js"></script>
</body>
</html>
