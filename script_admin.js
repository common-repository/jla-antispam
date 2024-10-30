window.onload = () =>{
	var isJLAAntispamPage = document.querySelector(".toplevel_page_jlaantispam_config_main");

	document.querySelectorAll(".outputDeleter").forEach((outputDeleter)=>{
		outputDeleter.addEventListener("click", (e)=>{
			e.target.parentNode.remove();
		});
	});

	document.querySelectorAll("[data-parentof]").forEach((parent)=>{
		checkbox = parent.querySelector("input[type='checkbox'");
		childDisabler(checkbox);
		checkbox.addEventListener("click", (cb)=>{ childDisabler(cb.target) });
	});
	function childDisabler(cb){
		parent = cb.closest('tr'); // tr parent
		el = parent.nextSibling;
		while (parent.getAttribute("data-parentof").split(",").includes(el.getAttribute("id"))) { // dès qu'on tombe sur un élément NON enfant, on arrêtela boucle
			checkbox.checked ?
				el.classList.remove("disabledChildFilter")
				: el.classList.add("disabledChildFilter");

			el = el.nextSibling;
		}
	}
	
	if(isJLAAntispamPage){
		arrayOptions = document.querySelectorAll(".arrayOption");
		arrayOptions.forEach((arrayOption)=>{
			arrayOption.querySelector(".btnAddWordToFollowingList") // clic bouton
				.addEventListener("click", (e)=>{
					addInputWord(e);
				})
				
			arrayOption.querySelector("input") // entrer output
				.addEventListener("keydown", (e)=>{
					if(e.code === "Enter") {
						addInputWord(e);
					}
				})
		});
	}

	function addInputWord(e){
		node = e.target;
		parent = node.parentNode;

		inputText = parent.querySelector("input[type='text'");
		list = parent.querySelector("ul");

		if(inputText.value){
			output =  document.createElement("output");
			output.innerText = inputText.value;
			
			input =  document.createElement("input");
			input.setAttribute("type","jlaantispam_hidden");
			input.setAttribute("name", list.getAttribute("data-name"));
			input.setAttribute("value",inputText.value);
			input.setAttribute("readonly", true);

			outputDeleter = document.createElement("div");
			outputDeleter.setAttribute("class", "outputDeleter");
			outputDeleter.addEventListener("click", (e)=>{
				e.target.parentNode.remove();
			});

			li = document.createElement("li");
			li.setAttribute("class","jlaantispam_word");

			li.appendChild(outputDeleter);
			li.appendChild(output);
			li.appendChild(input);
			list.appendChild(li);

			inputText.value = "";
			inputText.focus();
		}
	}
};