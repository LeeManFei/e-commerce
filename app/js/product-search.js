document.addEventListener('DOMContentLoaded', function() {
    const searchBtn = document.getElementById('search-btn');
    const searchInput = document.getElementById('search-input');

    searchBtn.addEventListener('click', function(){
       const query = searchInput.value.trim();
       const category = document.getElementById('category-select').value;
       const minPrice = document.getElementById('min-price').value;
       const maxPrice = document.getElementById('max-price').value;

       let url = "view_products.php?search=" + encodeURIComponent(query) + "&category=" + encodeURIComponent(category);
       if(minPrice) url += "&min_price=" + encodeURIComponent(minPrice);
       if(maxPrice) url += "&max_price=" + encodeURIComponent(maxPrice);

       window.location.href = url;
    });

    searchInput.addEventListener('keypress', function(e){
       if(e.key === "Enter"){
          e.preventDefault();
          searchBtn.click();
       }
    });
});
