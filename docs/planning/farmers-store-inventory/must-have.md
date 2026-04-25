# Main

This project will be an inventory managment system for Farmers Funiture. It must track all products primarily by SKU
and the AO number. The AO is more or less farmers in house serial number. They are all unique. SKU numbers just identify the particular type of product.

Each farmers warehouse will be identified by two pieces of data. The store number which are unique and the city in which the store is located. The primary id for each store will be its store number ie Store #207 Leeds, AL.

Each store row will be related to multiple users. Common user roles will be Manager, Credit Manager, DC_Warehouse, Warehouse Supervisor, Warehouse, Sales. Warehouse Supervisor and above will be able to edit inventory. Warehouse will have a limited subset of tools available.

So we will end up with these as a minimum set of tables.

* user
* role
* store
* product
* product_status (relational table to track if a product is damaged)
* product_image (to store records for images attached to damaged products)

## Project Motivation

Farmers currently has an "inventory system" (built via sharepoint or celerant, I think, it sucks), but it does not provide a means to track the huge amount of product that each store gets that is damaged which leads to store managers wasting a huge amount of time when trying to get needed product from another location. There is 254 locations in the south east US. We are building this application to provide a means for each store that is registered to easily track which products arrive damaged and which are in good order and can be sold or transferred to another location if needed.

Each product that is marked as damaged should also have images attached since it is possible for a product to be slightly damaged and still be sold at a discount, but store managers need to be able to see the damage so they can determine if they want to transfer the product before sending warehouse personel to pick the product up.

Ultimately it would also be useful if a user with the role DC_Warehouse could log in and generate reporting around what % of product has been recieved by each store that is damaged. This will hopefully provide insight to how well the Distrubution Center personel are handling the product during loading prior to shipping to each location; and if they are actively screening product for signs of damage before sending it to each location.

The prefered workflow is when a DC incoming shipment is processed at each location the application will provide a means to scan the barcode on each product (we will need to identify a php library that provides that functionality). I have images of a sample SKU card which has the SKU id and the Tag ID on every product that gets shipped. Once the manifest is processed then the store inventory can be updated, or possibly it can be updated in real time each time a product is scanned. Then once the product is being prepped for delivery or prepared to go to the sales floor if a damaged product is found then one can be modified and marked as damaged, pictures taken and it flagged as damaged. When the product is flagged as damaged a notification should be sent to the Manager for that location so that a PQA process can be started so that the store can be issued a credit on that particular product from the corporate office. The PQA process is outside the scope of this project.

### Project dependecies

This project will be built using my custom bleeding edge mezzio skeleton, which is already present. It will leverage the True Async runtime and the mezzio-async package we developed in another project to provide mezzio support for the true async runtime. Those parts are in working order. It will use HTMX for the front end client side code and we will use laminas view as the template layer. We will leverage SSE via HTMX for notifications. It will use Bootstrap 5.3+ for the css framework. We will be using mezzio/mezzio-auth backed by php sessions. We will be using the mezzio/mezzio-authorize package with laminas-acl support for access control list. I have custom packages that will provide that support and will handle the integration of those packages myself.

We will also be using webware/command-bus.

#### Database Choice

We will be using PhpDb for the database abstraction layer since I am one of the maintainers of that project. We need to explore how complicated it will be to provide Async support to MySQL via the new mysql X protocol or if we should just use PostGres since we already have working code for postgres. If possible I would prefer to use MySQL simply because I have a lot more experience with mysql than postgres but we will go with what is best for the project and what the research determines is the correct choice.

##### Code Requirements

True Async is 8.6.0-dev php build which is based on php 8.5 so we should have all 8.5 features available. We will use proper abstraction at all times and we will prefer composition over inheritance where its possible. We will avoid static usage when possible. We will enforce PER 3.0 and webware/coding-standard syntax throughout the project. The complicating factor there is that php-cs-fixer does not support true async.

##### Architecture

We will be using a "module" architecture in the sense that each namespace we add to /src/{module} will expose its own ConfigProvider and will be its own "module". We will most likely follow a Repository/Entity pattern for the database entry points and we may use Valinor for upcasting incoming request to entity types. Mezzio now has a package just for this. We may explore its usage here.

###### Chat answers

1. A manifest table is a great idea actually. This would allow for the creation of a perm record tying each product to its incoming manifest to provide much richer reporting. We can provide FK's to each products Tag ID.

2. AO and Tag ID is the same ID.

3. Vendor will just be a stored string for each product.

4. For status I think the following should cover it. Each product should support multiple of these options to be selected. Its possible that a product may be damaged, but also on the floor. A product may also be damaged and in the bargain_center.

  * overtock
  * damaged
  * floor
  * pending_pqa
  * bargain_center
  * reparable
  * non_reparable

  We will need a process order routine so that as the warehouse personel prep an order they can scan each item and it be removed from inventory. This is where we will most likely attach a customer name. Celerant handles the primary customer tracking, we would really only be interested in which customer got which product as a record of if they purchased a damaged piece. This is allowed and they would get a discount on it but its very useful information because at times we have customers that try to be slick and convince us that they got a product "new and undamaged" when in reality they bought it as-is at a discount but want us to then replace it a month later saying that was not the case. Celerant provides no way to track this data so it could be a huge win for the warehouse dept.

5. Transfer workflow is handled outside of this system, this system will just be used to provide the needed information as to whether the product is viable for transfer based on its condition.

6. Celerant provides the primary customer tracking we are just providing additional data that celerant does not track but should. Celerant is a "general purpose" inventory management system that Farmers uses. This application is a focused, company specific tool targeting the gaps in process left by Celerants missing features.

7. Managers can view other stores inventory and each products status, but they can not modify another stores inventory or another stores products status.

8. Local filesystem will be enough since when products are sold and the batch processing runs then each image attached that AO number should be removed since its no longer required.

9. These are used to categorize inventory. We probably need to provide a way to manage those so a relational table would probably be easiest. IIRC that stands for Major Code so a major_code table will probably be needed.

##### Follow up answers

1. I think just stored on the product will be enough. 

2. The scan out process will be similar but not identical. For product moving to the floor it should remain in inventory its status just changes. For actual deliveries/pick up then it will be removed from inventory since it will no longer be in store or available for sale or transfer.

3. I meant overstock, was just a typo. I am human after all :P