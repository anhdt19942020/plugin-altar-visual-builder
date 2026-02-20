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

  // Set Background (Demo Table - Front Facing)
  fabric.Image.fromURL(
    "https://placehold.co/1200x800/2a1a0a/C9A84C?text=Ban+Tho+Chinh+Dien+(Front+View)",
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

  let currentBase = null; // To store base altar product

  function addOverlayToCanvas(product) {
    if (!product.overlay_png) {
      alert("This product has no altar overlay configured.");
      return;
    }

    if (product.altar_type === "altar_base") {
      // Handle the main altar product
      fabric.Image.fromURL(
        product.overlay_png,
        function (img) {
          const scaleX = canvas.width / img.width;
          const scaleY = canvas.height / img.height;
          const scale = Math.min(scaleX, scaleY);

          img.set({
            scaleX: scale,
            scaleY: scale,
            originX: "center",
            originY: "center",
            left: canvas.width / 2,
            top: canvas.height / 2,
            selectable: false,
            evented: false,
          });

          canvas.setBackgroundImage(img, canvas.renderAll.bind(canvas));

          // Ensure we store all necessary data for cart
          currentBase = {
            id: parseInt(product.id),
            default_variation_id: parseInt(product.default_variation_id || 0),
            altar_type: product.altar_type,
            name: product.name,
          };
          updateItemCount();
        },
        { crossOrigin: "anonymous" },
      );
      return;
    }

    fabric.Image.fromURL(
      product.overlay_png,
      function (oImg) {
        const scale = parseFloat(product.overlay_scale) || 0.5;
        oImg.scale(scale);
        oImg.set({
          left: canvas.width / 2,
          top: canvas.height * 0.7, // Positioned on target surface area
          originX: "center",
          originY: "bottom",
          shadow: new fabric.Shadow({
            color: "rgba(0,0,0,0.3)",
            blur: 6,
            offsetX: 0, // Centered for front view
            offsetY: 3,
          }),
        });

        // Attach metadata to object
        oImg.productId = product.id;
        oImg.variationId = product.default_variation_id || 0;
        oImg.altarType = product.altar_type;
        oImg.overlayScale = scale;

        canvas.add(oImg);
        canvas.setActiveObject(oImg);
        updateItemCount();
        sortObjectsByDepth();
      },
      { crossOrigin: "anonymous" },
    );
  }

  // --- REALISTIC DEPTH & PERSPECTIVE ---
  function sortObjectsByDepth() {
    const objects = canvas.getObjects().filter((o) => o.productId);

    // Sort items so those further down (higher Y) are in front
    objects.sort((a, b) => {
      const aBottom = a.top; // originY is bottom
      const bBottom = b.top;
      return aBottom - bBottom;
    });

    objects.forEach((obj) => {
      applyPerspectiveScale(obj);
      obj.bringToFront();
    });

    canvas.renderAll();
  }

  /**
   * Adjusts object scale based on vertical position to simulate depth
   * Higher Y (closer) = Larger | Lower Y (further) = Smaller
   */
  function applyPerspectiveScale(obj) {
    if (!obj.overlayScale) return;

    // The range of the altar surface in front view
    const floorTop = canvas.height * 0.5; // Back edge of the table
    const floorBottom = canvas.height * 0.85; // Front edge of the table
    const currentY = obj.top;

    // Subtle scale factor for front view: almost 1:1
    const factorBack = 0.95;
    const factorFront = 1.05;

    let relativePos = (currentY - floorTop) / (floorBottom - floorTop);
    relativePos = Math.max(0, Math.min(1, relativePos)); // Clamp 0-1

    const perspectiveFactor =
      factorBack + relativePos * (factorFront - factorBack);

    // Update Scale
    const targetScale = obj.overlayScale * perspectiveFactor;
    if (Math.abs(obj.scaleX - targetScale) > 0.005) {
      obj.scale(targetScale);
    }

    // Dynamic Shadow based on depth (Subtle for front view)
    if (obj.shadow) {
      obj.shadow.blur = 4 + relativePos * 6;
      obj.shadow.offsetX = 0; // Keep centered
      obj.shadow.offsetY = 1 + relativePos * 4;
      obj.shadow.color = `rgba(0,0,0,${0.2 + relativePos * 0.2})`;
    }
  }

  // selection highlight
  canvas.on("selection:created", (e) => {
    if (e.target.productId) {
      e.target.set("stroke", "rgba(201,168,76,0.5)");
      e.target.set("strokeWidth", 2);
    }
  });
  canvas.on("selection:cleared", (e) => {
    canvas.getObjects().forEach((obj) => {
      obj.set("stroke", null);
      obj.set("strokeWidth", 0);
    });
  });

  canvas.on("object:moving", sortObjectsByDepth);
  canvas.on("object:scaling", (e) => {
    // If the user manually scales, update their "base" scale so perspective still works
    const obj = e.target;
    if (obj.overlayScale) {
      // Back-calculate what the base scale should be
      const floorTop = canvas.height * 0.2;
      const floorBottom = canvas.height * 0.9;
      let relativePos = (obj.top - floorTop) / (floorBottom - floorTop);
      relativePos = Math.max(0, Math.min(1, relativePos));

      const perspectiveFactor = 0.75 + relativePos * (1.15 - 0.75);
      obj.overlayScale = obj.scaleX / perspectiveFactor;
    }
  });

  // --- ITEM COUNT BADGE ---
  const itemCountEl = document.getElementById("altar-item-count");

  function updateItemCount() {
    let count = canvas.getObjects().filter((o) => o.productId).length;
    if (currentBase) count++;

    if (itemCountEl) {
      if (count === 0) {
        itemCountEl.textContent = "";
      } else {
        itemCountEl.textContent = count + " vật phẩm đã được thêm";
      }
    }
  }

  canvas.on("object:removed", updateItemCount);

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

    if (objects.length === 0 && !currentBase) {
      alert(cfg.messages.empty_canvas);
      return;
    }

    addToCartBtn.disabled = true;
    statusDiv.innerHTML = `<p>${cfg.messages.processing}</p>`;

    // Aggregate Items by productId:variationId
    const itemCounts = {}; // { "id:vid": { productId, variationId, type, qty } }

    // Add Base Altar first if it exists
    if (currentBase && currentBase.id) {
      const bId = parseInt(currentBase.id);
      const bVId = parseInt(currentBase.default_variation_id || 0);
      const key = `${bId}:${bVId}`;
      itemCounts[key] = {
        product_id: bId,
        variation_id: bVId,
        type: currentBase.altar_type || "altar_base",
        qty: 1,
      };
    }

    objects.forEach((obj) => {
      const pId = parseInt(obj.productId);
      const vId = parseInt(obj.variationId || 0);
      const key = `${pId}:${vId}`;

      if (!itemCounts[key]) {
        itemCounts[key] = {
          product_id: pId,
          variation_id: vId,
          type: obj.altarType || "",
          qty: 0,
        };
      }
      itemCounts[key].qty++;
    });

    const finalItems = Object.values(itemCounts);
    console.log("Altar Configurator: Sending Bundle Items:", finalItems);

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
