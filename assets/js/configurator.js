document.addEventListener("DOMContentLoaded", function () {
  const container = document.getElementById("altar-configurator-container");
  if (!container) return;

  // Defensive check for localized config
  const cfg =
    typeof altarConfig !== "undefined"
      ? altarConfig
      : {
          ajax_url: "/wp-admin/admin-ajax.php",
          nonce: "",
          messages: {
            searching: "Searching...",
            no_results: "No products found.",
            processing: "Processing...",
            success: "Success!",
            add_to_canvas: "Add",
          },
        };

  const canvasElement = document.getElementById("altar-canvas");
  const wrapper = canvasElement.parentElement;

  // Initialize Fabric Canvas
  const canvas = new fabric.Canvas("altar-canvas", {
    width: wrapper.clientWidth,
    height: 500, // Initial height
    backgroundColor: "#f5f5f5",
  });

  // Make responsive
  function resizeCanvas() {
    const width = wrapper.clientWidth;
    const scale = width / canvas.width;

    canvas.setDimensions({ width: width, height: width * 0.7 });
    canvas.renderAll();
  }

  window.addEventListener("resize", resizeCanvas);
  resizeCanvas();

  // Set Background (Demo Table)
  fabric.Image.fromURL(
    "https://placehold.co/800x600/3d2b1f/white?text=Altar+Table",
    function (img) {
      img.set({
        scaleX: canvas.width / img.width,
        scaleY: canvas.height / img.height,
        originX: "left",
        originY: "top",
        selectable: false,
        evented: false,
      });
      canvas.setBackgroundImage(img, canvas.renderAll.bind(canvas));
    },
  );

  // --- PRODUCT SEARCH LOGIC ---
  const searchInput = document.getElementById("altar-search-input");
  const searchBtn = document.getElementById("altar-search-btn");
  const resultsDiv = document.getElementById("altar-product-results");

  function searchProducts() {
    if (!cfg.nonce) {
      console.error("Altar Configurator: Nonce is missing. Search may fail.");
    }

    const query = searchInput.value.trim();
    resultsDiv.innerHTML = `<p>${cfg.messages.searching}</p>`;

    const url = new URL(cfg.ajax_url);
    url.searchParams.append("action", "altar_search_products");
    url.searchParams.append("nonce", cfg.nonce);
    url.searchParams.append("q", query);

    fetch(url)
      .then((res) => res.json())
      .then((data) => {
        if (data.success && data.data.length > 0) {
          renderProducts(data.data);
        } else {
          resultsDiv.innerHTML = `<p>${cfg.messages.no_results}</p>`;
        }
      })
      .catch((err) => {
        resultsDiv.innerHTML = `<p style="color:red;">Error: ${err.message}</p>`;
      });
  }

  function renderProducts(products) {
    resultsDiv.innerHTML = "";
    products.forEach((product) => {
      const card = document.createElement("div");
      card.className = "altar-product-card";
      card.innerHTML = `
        <div class="card-thumb"><img src="${product.image}" loading="lazy"></div>
        <div class="card-info">
          <span class="product-name">${product.name}</span>
          <span class="product-price">${product.price_html}</span>
        </div>
        <button class="add-to-canvas-btn" data-product='${JSON.stringify(product).replace(/'/g, "&apos;")}'>
          ${cfg.messages.add_to_canvas}
        </button>
      `;
      resultsDiv.appendChild(card);
    });

    // Add click events to buttons
    resultsDiv.querySelectorAll(".add-to-canvas-btn").forEach((btn) => {
      btn.addEventListener("click", function () {
        const product = JSON.parse(this.dataset.product);
        addOverlayToCanvas(product);
      });
    });
  }

  searchBtn.addEventListener("click", searchProducts);
  searchInput.addEventListener("keypress", (e) => {
    if (e.key === "Enter") searchProducts();
  });

  function addOverlayToCanvas(product) {
    if (!product.overlay_png) {
      alert("This product has no altar overlay configured.");
      return;
    }

    fabric.Image.fromURL(
      product.overlay_png,
      function (oImg) {
        const scale = parseFloat(product.overlay_scale) || 0.5;
        oImg.scale(scale);
        oImg.set({
          left: canvas.width / 2,
          top: canvas.height / 2,
          originX: "center",
          originY: "center",
        });

        // Attach metadata to object
        oImg.productId = product.id;
        oImg.variationId = product.default_variation_id || 0;
        oImg.altarType = product.altar_type;
        oImg.overlayScale = scale;

        canvas.add(oImg);
        canvas.setActiveObject(oImg);
      },
      { crossOrigin: "anonymous" },
    );
  }

  // --- CANVAS CONTROLS (Z-INDEX) ---
  // (You can add UI buttons for these later, logic is simple:)
  /*
  function bringFront() { canvas.getActiveObject()?.bringToFront(); }
  function sendBack() { canvas.getActiveObject()?.sendToBack(); }
  */

  // Handle Remove Item (Delete Key)
  window.addEventListener("keydown", function (e) {
    if (e.key === "Delete" || e.key === "Backspace") {
      const activeObject = canvas.getActiveObject();
      if (activeObject && !activeObject.isType("text")) {
        canvas.remove(activeObject);
      }
    }
  });

  // --- ADD TO CART ACTION ---
  const addToCartBtn = document.getElementById("add-to-cart-btn");
  const statusDiv = document.getElementById("altar-status");

  addToCartBtn.addEventListener("click", function () {
    const objects = canvas.getObjects().filter((obj) => obj.productId);

    if (objects.length === 0) {
      alert(cfg.messages.empty_canvas);
      return;
    }

    addToCartBtn.disabled = true;
    statusDiv.innerHTML = `<p>${cfg.messages.processing}</p>`;

    // Aggregate Items by productId:variationId
    const itemCounts = {}; // { "id:vid": { productId, variationId, type, qty } }

    objects.forEach((obj) => {
      const key = `${obj.productId}:${obj.variationId}`;
      if (!itemCounts[key]) {
        itemCounts[key] = {
          product_id: obj.productId,
          variation_id: obj.variationId,
          type: obj.altarType || "",
          qty: 0,
        };
      }
      itemCounts[key].qty++;
    });

    const finalItems = Object.values(itemCounts);

    // Get Preview DataURL
    const previewData = canvas.toDataURL({
      format: "jpeg",
      quality: 0.8,
    });

    // AJAX Request
    const formData = new FormData();
    formData.append("action", "altar_add_bundle_to_cart");
    formData.append("nonce", cfg.nonce);
    formData.append("items", JSON.stringify(finalItems));
    formData.append("preview_image", previewData);
    formData.append(
      "layout_json",
      JSON.stringify(canvas.toJSON(["productId", "variationId", "altarType"])),
    );

    fetch(cfg.ajax_url, {
      method: "POST",
      body: formData,
    })
      .then((res) => res.json())
      .then((data) => {
        if (data.success) {
          statusDiv.innerHTML = `<p style="color:green;">${cfg.messages.success}</p>`;
          window.location.href = data.data.cart_url;
        } else {
          throw new Error(data.data || "Unknown error");
        }
      })
      .catch((err) => {
        console.error(err);
        statusDiv.innerHTML = `<p style="color:red;">Error: ${err.message}</p>`;
        addToCartBtn.disabled = false;
      });
  });
});
