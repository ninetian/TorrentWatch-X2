<div class="dialog_window" id="report_bug">
    <form action="#" id="report_form">
    <div><label class="item">Summary:
        <input id='Summary' type='text' class='text' name='Summary'?>
    </label></div>
    <div><label class="item">Name:
        <input id='Name' type='text' class='text' name='Name'?>
    </label></div>
    <div><label class="item">Email:
        <input id='Email' type='text' class='text' name='Email'?>
    </label></div>
    <div><label class="item">Priority:</label>
    <select id='Priority' name="Priority">
        <option value="Priority-Low">
            Low
        </option>
        <option value="Priority-Medium">
            Medium
        </option>
        <option value="Priority-High">
            High
        </option>
        <option value="Priority-Critical">
            Critical
        </option>
    </select>
    </div>
    <div><label class="item">Description:</label>
        <textarea id='Description' type='text' rows=10 class='text description' name='Description'/>
    </div>
    <div class="buttonContainer">
        <a class="button" id="Submit Bug" href="#" onclick="$.submitBug()">Submit</a> 
        <a class='toggleDialog button' href='#'>Close</a> 
    </div>
    </form>
</div>
