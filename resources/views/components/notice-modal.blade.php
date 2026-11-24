<!-- ================= NOTICE MODAL ================= -->
<div class="modal-overlay" id="notice-modal" role="dialog" aria-modal="true" aria-labelledby="modal-title">
  <div class="modal">
    <h3 id="modal-title">Post a notice</h3>
    <div class="field">
      <label for="nm-title">Title</label>
      <input id="nm-title" type="text" maxlength="80" placeholder="e.g. Printer on floor 2 is grumpy again">
    </div>
    <div class="field">
      <label for="nm-body">Details</label>
      <textarea id="nm-body" rows="3" maxlength="400" placeholder="What should people know or do?"></textarea>
    </div>
    <div class="field-grid">
      <div class="field">
        <label for="nm-cat">Category</label>
        <select id="nm-cat">
          <option value="general">General</option>
          <option value="it">IT Notice</option>
          <option value="reminder">Reminder</option>
        </select>
      </div>
      <div class="field">
        <label for="nm-prio">Priority</label>
        <select id="nm-prio">
          <option value="normal">Normal</option>
          <option value="high">High</option>
        </select>
      </div>
    </div>
    <label class="check-row" style="margin-top:6px">
      <input type="checkbox" id="nm-pin"> Pin to top of the board
    </label>
    <div class="modal-actions">
      <button type="button" class="btn btn-ghost" id="nm-cancel">Cancel</button>
      <button type="button" class="btn" id="nm-save">Publish</button>
    </div>
  </div>
</div>
