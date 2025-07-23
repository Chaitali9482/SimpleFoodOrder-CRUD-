from app import app, db
from flask import render_template, redirect, url_for, request, flash, session, jsonify
from werkzeug.security import generate_password_hash, check_password_hash
from models import Restaurant, Menu, Customer, Order, OrderItem, Admin

# Sample data
def add_sample_data():
    with app.app_context():
        # Check if we already have data
        if Restaurant.query.count() > 0:
            return
        
        # Add sample restaurants
        restaurants = [
            Restaurant(name='Burger Palace', location='Downtown'),
            Restaurant(name='Pizza Heaven', location='Westside'),
            Restaurant(name='Taco Fiesta', location='Northside'),
            Restaurant(name='Sushi World', location='Eastside'),
            Restaurant(name='Pasta Paradise', location='Southside')
        ]
        
        db.session.add_all(restaurants)
        db.session.commit()
        
        # Add menu items
        menu_items = [
            # Burger Palace items
            Menu(restaurant_id=1, item_name='Veggie Burger', price=799, 
                 description='Plant-based patty with avocado, sprouts and vegan mayo'),
            Menu(restaurant_id=1, item_name='Chicken Sandwich', price=799, 
                 description='Grilled chicken breast with lettuce, tomato and honey mustard'),
            Menu(restaurant_id=1, item_name='French Fries', price=319, 
                 description='Crispy, golden fries with seasoning'),
            
            # Pizza Heaven items
            Menu(restaurant_id=2, item_name='Margherita Pizza', price=1039, 
                 description='Classic pizza with tomato sauce, mozzarella and fresh basil'),
            Menu(restaurant_id=2, item_name='Pepperoni Pizza', price=1199, 
                 description='Pizza with tomato sauce, mozzarella and pepperoni'),
            Menu(restaurant_id=2, item_name='Vegetarian Pizza', price=1119, 
                 description='Pizza with bell peppers, onions, mushrooms, olives and mozzarella'),
            Menu(restaurant_id=2, item_name='Chicken Pizza', price=1359, 
                 description='Pizza with chicken, onions, bell peppers and mozzarella'),
            Menu(restaurant_id=2, item_name='Garlic Bread', price=399, 
                 description='Toasted bread with garlic butter and herbs'),
            
            # Taco Fiesta items
            Menu(restaurant_id=3, item_name='Chicken Taco', price=239, 
                 description='Grilled chicken in a corn tortilla with lettuce, cheese and salsa'),
            Menu(restaurant_id=3, item_name='Vegetarian Taco', price=239, 
                 description='Black beans and grilled vegetables in a corn tortilla with lettuce and salsa'),
            Menu(restaurant_id=3, item_name='Nachos Supreme', price=719, 
                 description='Tortilla chips with beans, cheese, jalapeños, sour cream and guacamole'),
            Menu(restaurant_id=3, item_name='Veggie Burrito', price=799, 
                 description='Large flour tortilla filled with rice, beans, vegetables, cheese and salsa'),
            Menu(restaurant_id=3, item_name='Chicken Burrito', price=799, 
                 description='Large flour tortilla filled with rice, chicken, cheese and salsa'),
            
            # Sushi World items
            Menu(restaurant_id=4, item_name='California Roll', price=559, 
                 description='Crab, avocado and cucumber roll'),
            Menu(restaurant_id=4, item_name='Spicy Tuna Roll', price=639, 
                 description='Fresh tuna with spicy mayo roll'),
            Menu(restaurant_id=4, item_name='Salmon Nigiri', price=479, 
                 description='Fresh salmon slices on seasoned rice (2 pieces)'),
            Menu(restaurant_id=4, item_name='Vegetable Tempura', price=719, 
                 description='Assorted vegetables fried in light tempura batter'),
            Menu(restaurant_id=4, item_name='Miso Soup', price=319, 
                 description='Traditional Japanese soup with tofu and seaweed'),
            
            # Pasta Paradise items
            Menu(restaurant_id=5, item_name='Vegetable Pasta', price=959, 
                 description='Spaghetti with rich vegetable sauce and parmesan'),
            Menu(restaurant_id=5, item_name='Fettuccine Alfredo', price=1039, 
                 description='Fettuccine pasta in creamy garlic parmesan sauce'),
            Menu(restaurant_id=5, item_name='Vegetable Lasagna', price=1119, 
                 description='Layers of pasta, vegetable sauce, and three cheeses'),
            Menu(restaurant_id=5, item_name='Garlic Shrimp Linguine', price=1279, 
                 description='Linguine with garlic shrimp in white wine sauce'),
            Menu(restaurant_id=5, item_name='Tiramisu', price=559, 
                 description='Classic Italian dessert with coffee and mascarpone')
        ]
        
        db.session.add_all(menu_items)
        
        # Add admin user
        if Admin.query.filter_by(username='admin').first() is None:
            admin = Admin(username='admin', password=generate_password_hash('admin123'))
            db.session.add(admin)
        
        db.session.commit()
        print("Sample data added successfully!")

# Add sample data
add_sample_data()

# Initialize cart
def init_cart():
    if 'cart' not in session:
        session['cart'] = []

# Home route
@app.route('/')
def index():
    # Get featured restaurants (4 random restaurants)
    featured_restaurants = Restaurant.query.order_by(db.func.random()).limit(4).all()
    return render_template('index.html', 
                           featured_restaurants=featured_restaurants, 
                           title='SimpleEats - Home')

# All restaurants route
@app.route('/restaurants')
def restaurants():
    all_restaurants = Restaurant.query.order_by(Restaurant.name).all()
    return render_template('restaurants.html', 
                           restaurants=all_restaurants, 
                           title='All Restaurants')

# Restaurant menu route
@app.route('/menu/<int:restaurant_id>')
def menu(restaurant_id):
    init_cart()
    restaurant = Restaurant.query.get_or_404(restaurant_id)
    menu_items = Menu.query.filter_by(restaurant_id=restaurant_id).order_by(Menu.item_name).all()
    
    # Get cart for this restaurant
    cart = session.get('cart', [])
    
    # Transform cart items to include menu object
    cart_with_details = []
    for item in cart:
        if item.get('restaurant_id') == restaurant_id:
            menu_item = Menu.query.get(item.get('menu_id'))
            if menu_item:
                cart_with_details.append({
                    'menu': menu_item,
                    'quantity': item.get('quantity')
                })
    
    return render_template('menu.html', 
                           restaurant=restaurant, 
                           menu_items=menu_items, 
                           cart=cart_with_details,
                           title=f'Menu - {restaurant.name}')

# Order route
@app.route('/order/<int:restaurant_id>', methods=['GET', 'POST'])
def order(restaurant_id):
    init_cart()
    restaurant = Restaurant.query.get_or_404(restaurant_id)
    menu_items = Menu.query.filter_by(restaurant_id=restaurant_id).order_by(Menu.item_name).all()
    
    # Handle adding items to cart
    if request.method == 'POST' and 'add_to_cart' in request.form:
        menu_id = request.form.get('menu_id', type=int)
        quantity = request.form.get('quantity', type=int, default=1)
        
        if menu_id and quantity and quantity > 0:
            menu_item = Menu.query.get(menu_id)
            
            if menu_item and menu_item.restaurant_id == restaurant_id:
                # Check if item already in cart
                cart = session['cart']
                item_index = None
                
                for i, item in enumerate(cart):
                    if item.get('menu_id') == menu_id:
                        item_index = i
                        break
                
                if item_index is not None:
                    # Update quantity if item already in cart
                    cart[item_index]['quantity'] += quantity
                else:
                    # Add new item to cart
                    cart.append({
                        'restaurant_id': restaurant_id,
                        'menu_id': menu_id,
                        'name': menu_item.item_name,
                        'price': float(menu_item.price),
                        'quantity': quantity
                    })
                
                session['cart'] = cart
                flash('Item added to cart!', 'success')
    
    # Filter cart items for current restaurant
    cart_items = [item for item in session.get('cart', []) if item.get('restaurant_id') == restaurant_id]
    
    return render_template('order.html', 
                           restaurant=restaurant, 
                           menu_items=menu_items, 
                           cart_items=cart_items, 
                           title=f'Order - {restaurant.name}')

# Remove item from cart
@app.route('/remove_item/<int:restaurant_id>/<int:item_index>')
def remove_item(restaurant_id, item_index):
    if 'cart' in session and 0 <= item_index < len(session['cart']):
        session['cart'].pop(item_index)
        session.modified = True
        flash('Item removed from cart!', 'success')
    
    return redirect(url_for('order', restaurant_id=restaurant_id))

# Checkout route
@app.route('/checkout/<int:restaurant_id>', methods=['GET', 'POST'])
def checkout(restaurant_id):
    init_cart()
    restaurant = Restaurant.query.get_or_404(restaurant_id)
    
    # Get cart for this restaurant
    cart = session.get('cart', [])
    
    # Transform cart items to include menu object (like in menu view)
    cart_with_details = []
    for item in cart:
        if item.get('restaurant_id') == restaurant_id:
            menu_item = Menu.query.get(item.get('menu_id'))
            if menu_item:
                cart_with_details.append({
                    'menu': menu_item,
                    'quantity': item.get('quantity')
                })
    
    if not cart_with_details:
        flash('Your cart is empty. Please add items before checking out.', 'warning')
        return redirect(url_for('menu', restaurant_id=restaurant_id))
    
    if request.method == 'POST':
        name = request.form.get('name')
        email = request.form.get('email')
        
        if not name or not email:
            flash('Please fill in all fields.', 'danger')
        else:
            # Check if customer exists, otherwise create new customer
            customer = Customer.query.filter_by(email=email).first()
            
            if customer:
                customer.name = name  # Update name if different
            else:
                customer = Customer(name=name, email=email)
                db.session.add(customer)
                db.session.flush()  # Get ID for new customer
            
            # Create order
            order = Order(customer_id=customer.id, restaurant_id=restaurant_id, status='Pending')
            db.session.add(order)
            db.session.flush()  # Get ID for new order
            
            # Add order items
            for item in cart_with_details:
                order_item = OrderItem(
                    order_id=order.id,
                    menu_id=item['menu'].id,
                    quantity=item['quantity']
                )
                db.session.add(order_item)
            
            # Commit all changes
            db.session.commit()
            
            # Clear cart for this restaurant
            session['cart'] = [item for item in session.get('cart', []) if item.get('restaurant_id') != restaurant_id]
            
            flash('Order placed successfully!', 'success')
            return render_template('order_confirmation.html', 
                                  restaurant=restaurant,
                                  cart=cart_with_details,
                                  customer_name=name,
                                  customer_email=email,
                                  order_id=order.id,
                                  order_date=order.order_date,
                                  title='Order Confirmation')
    
    # Calculate total from the detailed cart
    total = sum(item['menu'].price * item['quantity'] for item in cart_with_details)
    
    return render_template('checkout.html', 
                          restaurant=restaurant, 
                          cart=cart_with_details, 
                          total=total, 
                          title='Checkout')

# Order history route
@app.route('/order_history', methods=['GET', 'POST'])
def order_history():
    orders = []
    customer = None
    email_submitted = False
    
    if request.method == 'POST':
        email = request.form.get('email')
        email_submitted = True
        
        if email:
            customer = Customer.query.filter_by(email=email).first()
            
            if customer:
                orders = Order.query.filter_by(customer_id=customer.id).order_by(Order.order_date.desc()).all()
    
    return render_template('order_history.html', 
                          orders=orders, 
                          customer=customer, 
                          email_submitted=email_submitted, 
                          title='Order History')

# Admin routes
@app.route('/admin/login', methods=['GET', 'POST'])
def admin_login():
    if 'admin_logged_in' in session and session['admin_logged_in']:
        return redirect(url_for('admin_dashboard'))
    
    error = None
    
    if request.method == 'POST':
        username = request.form.get('username')
        password = request.form.get('password')
        
        if username and password:
            admin = Admin.query.filter_by(username=username).first()
            
            if admin and admin.password and check_password_hash(admin.password, password):
                session['admin_logged_in'] = True
                session['admin_id'] = admin.id
                return redirect(url_for('admin_dashboard'))
            else:
                error = 'Invalid username or password'
        else:
            error = 'Please enter both username and password'
    
    return render_template('admin/login.html', error=error, title='Admin Login')

@app.route('/admin/logout')
def admin_logout():
    session.pop('admin_logged_in', None)
    session.pop('admin_id', None)
    return redirect(url_for('admin_login'))

@app.route('/admin')
def admin_index():
    return redirect(url_for('admin_dashboard'))

@app.route('/admin/dashboard')
def admin_dashboard():
    if 'admin_logged_in' not in session or not session['admin_logged_in']:
        return redirect(url_for('admin_login'))
    
    # Get statistics for dashboard
    restaurant_count = Restaurant.query.count()
    menu_count = Menu.query.count()
    customer_count = Customer.query.count()
    order_count = Order.query.count()
    pending_count = Order.query.filter_by(status='Pending').count()
    
    # Recent orders
    recent_orders = db.session.query(
        Order, Customer.name.label('customer_name'), Restaurant.name.label('restaurant_name')
    ).join(Customer).join(Restaurant).order_by(Order.order_date.desc()).limit(5).all()
    
    return render_template('admin/dashboard.html', 
                          restaurant_count=restaurant_count, 
                          menu_count=menu_count, 
                          customer_count=customer_count, 
                          order_count=order_count, 
                          pending_count=pending_count, 
                          recent_orders=recent_orders, 
                          title='Admin Dashboard')

@app.route('/admin/restaurants', methods=['GET', 'POST'])
def admin_restaurants():
    if 'admin_logged_in' not in session or not session['admin_logged_in']:
        return redirect(url_for('admin_login'))
    
    restaurants = Restaurant.query.all()
    restaurant_to_edit = None
    
    # Handle form submission
    if request.method == 'POST':
        if 'save_restaurant' in request.form:
            name = request.form.get('name')
            location = request.form.get('location')
            restaurant_id = request.form.get('id')
            
            if name and location:
                if restaurant_id:  # Update existing
                    restaurant = Restaurant.query.get(restaurant_id)
                    if restaurant:
                        restaurant.name = name
                        restaurant.location = location
                        flash('Restaurant updated successfully!', 'success')
                    else:
                        flash('Restaurant not found.', 'danger')
                else:  # Create new
                    restaurant = Restaurant(name=name, location=location)
                    db.session.add(restaurant)
                    flash('Restaurant added successfully!', 'success')
                
                db.session.commit()
                return redirect(url_for('admin_restaurants'))
        
        elif 'delete_restaurant' in request.form:
            restaurant_id = request.form.get('id')
            
            if restaurant_id:
                restaurant = Restaurant.query.get(restaurant_id)
                
                if restaurant:
                    # Check if restaurant has orders
                    has_orders = Order.query.filter_by(restaurant_id=restaurant_id).first() is not None
                    
                    if has_orders:
                        flash('Cannot delete restaurant with existing orders.', 'danger')
                    else:
                        db.session.delete(restaurant)
                        db.session.commit()
                        flash('Restaurant deleted successfully!', 'success')
                else:
                    flash('Restaurant not found.', 'danger')
                
                return redirect(url_for('admin_restaurants'))
    
    # Handle edit request
    if 'edit' in request.args:
        restaurant_id = request.args.get('edit')
        restaurant_to_edit = Restaurant.query.get(restaurant_id)
    
    return render_template('admin/restaurants.html', 
                          restaurants=restaurants, 
                          restaurant_to_edit=restaurant_to_edit, 
                          title='Manage Restaurants')

@app.route('/admin/menus', methods=['GET', 'POST'])
def admin_menus():
    if 'admin_logged_in' not in session or not session['admin_logged_in']:
        return redirect(url_for('admin_login'))
    
    restaurants = Restaurant.query.order_by(Restaurant.name).all()
    menu_to_edit = None
    filter_restaurant_id = request.args.get('restaurant_id', type=int)
    
    # Get menu items with optional filter
    if filter_restaurant_id:
        menu_items = Menu.query.filter_by(restaurant_id=filter_restaurant_id).order_by(Menu.item_name).all()
    else:
        menu_items = Menu.query.order_by(Menu.item_name).all()
    
    # Handle form submission
    if request.method == 'POST':
        if 'save_menu' in request.form:
            restaurant_id = request.form.get('restaurant_id', type=int)
            item_name = request.form.get('item_name')
            price = request.form.get('price', type=float)
            description = request.form.get('description')
            menu_id = request.form.get('id')
            
            if restaurant_id and item_name and price:
                if menu_id:  # Update existing
                    menu_item = Menu.query.get(menu_id)
                    if menu_item:
                        menu_item.restaurant_id = restaurant_id
                        menu_item.item_name = item_name
                        menu_item.price = price
                        menu_item.description = description
                        flash('Menu item updated successfully!', 'success')
                    else:
                        flash('Menu item not found.', 'danger')
                else:  # Create new
                    menu_item = Menu(
                        restaurant_id=restaurant_id,
                        item_name=item_name,
                        price=price,
                        description=description
                    )
                    db.session.add(menu_item)
                    flash('Menu item added successfully!', 'success')
                
                db.session.commit()
                
                # Preserve filter
                if filter_restaurant_id:
                    return redirect(url_for('admin_menus', restaurant_id=filter_restaurant_id))
                else:
                    return redirect(url_for('admin_menus'))
        
        elif 'delete_menu' in request.form:
            menu_id = request.form.get('id')
            
            if menu_id:
                menu_item = Menu.query.get(menu_id)
                
                if menu_item:
                    # Check if menu item is used in any orders
                    has_orders = OrderItem.query.filter_by(menu_id=menu_id).first() is not None
                    
                    if has_orders:
                        flash('Cannot delete menu item with existing orders.', 'danger')
                    else:
                        db.session.delete(menu_item)
                        db.session.commit()
                        flash('Menu item deleted successfully!', 'success')
                else:
                    flash('Menu item not found.', 'danger')
                
                # Preserve filter
                if filter_restaurant_id:
                    return redirect(url_for('admin_menus', restaurant_id=filter_restaurant_id))
                else:
                    return redirect(url_for('admin_menus'))
    
    # Handle edit request
    if 'edit' in request.args:
        menu_id = request.args.get('edit')
        menu_to_edit = Menu.query.get(menu_id)
    
    return render_template('admin/menus.html', 
                          restaurants=restaurants, 
                          menu_items=menu_items, 
                          menu_to_edit=menu_to_edit, 
                          filter_restaurant_id=filter_restaurant_id, 
                          title='Manage Menus')

@app.route('/admin/orders', methods=['GET', 'POST'])
def admin_orders():
    if 'admin_logged_in' not in session or not session['admin_logged_in']:
        return redirect(url_for('admin_login'))
    
    restaurants = Restaurant.query.order_by(Restaurant.name).all()
    view_order = None
    order_items = []
    
    # Handle status update
    if request.method == 'POST' and 'update_status' in request.form:
        order_id = request.form.get('order_id')
        status = request.form.get('status')
        
        if order_id and status in ['Pending', 'Completed']:
            order = Order.query.get(order_id)
            if order:
                order.status = status
                db.session.commit()
                flash('Order status updated successfully!', 'success')
            else:
                flash('Order not found.', 'danger')
    
    # Handle order deletion
    if request.method == 'POST' and 'delete_order' in request.form:
        order_id = request.form.get('order_id')
        
        if order_id:
            order = Order.query.get(order_id)
            db.session.delete(order)
            db.session.commit()
            flash('Order deleted successfully!', 'success')
            return redirect(url_for('admin_orders'))
    
    # Check for filters
    status_filter = request.args.get('status', 'All')
    restaurant_filter = request.args.get('restaurant_id', type=int)
    
    # View specific order
    if 'view' in request.args:
        order_id = request.args.get('view')
        view_order = Order.query.get(order_id)
        
        if view_order:
            view_order.customer = Customer.query.get(view_order.customer_id)
            view_order.restaurant_obj = Restaurant.query.get(view_order.restaurant_id)
            
            # Get order items
            order_items = db.session.query(
                OrderItem, Menu.item_name, Menu.price
            ).join(Menu).filter(OrderItem.order_id == order_id).all()
    
    # Fetch orders with filters
    query = db.session.query(
        Order, Customer.name.label('customer_name'), Customer.email.label('customer_email'),
        Restaurant.name.label('restaurant_name')
    ).join(Customer).join(Restaurant)
    
    if status_filter != 'All':
        query = query.filter(Order.status == status_filter)
    
    if restaurant_filter:
        query = query.filter(Order.restaurant_id == restaurant_filter)
    
    orders = query.order_by(Order.order_date.desc()).all()
    
    return render_template('admin/orders.html', 
                          restaurants=restaurants, 
                          orders=orders, 
                          view_order=view_order, 
                          order_items=order_items, 
                          status_filter=status_filter, 
                          restaurant_filter=restaurant_filter, 
                          title='Manage Orders')

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=5000, debug=True)