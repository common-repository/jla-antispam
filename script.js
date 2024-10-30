window.onload = () =>{
	var form = document.querySelector(".wpcf7-form");
	if(form){
		var price1 = form.querySelector("#price1");
		var price2 = form.querySelector("#price2");
		var submit = form.querySelector('.wpcf7-submit');
		
		if( price1 && price2 ){
			submit.addEventListener('click', () => {
				price2.setAttribute("value", price1.value); // injecte dans le price2 la valeur de price1
			});
		}
	}
};