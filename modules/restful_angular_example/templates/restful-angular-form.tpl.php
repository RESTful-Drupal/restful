
<div ng-app="restfulApp" ng-controller="MainCtrl">
  <div class="explanation">
    This form delibertly allows sending invalid data from the client side, to
    show the response from the RESTful server.
  </div>

  <form name="article" ng-submit="submitForm()">

    <div class="text">
      <label>Title</label>
      <input id="label" name="label" type="text" ng-model="data.label" placeholder="See how many characters are needed to validate..." ng-required="required" size="60">

      <div class="errors">
        <ul ng-show="serverSide.data.errors.label">
          <li ng-repeat="error in serverSide.data.errors.label">{{error}}</li>
        </ul>
      </div>

    </div>

    <div class="textarea">
      <label>Description</label>
      <textarea id="body" name="body" type="textarea" ng-model="data.body" placeholder="Type some description. See which word is required..." rows="3" ng-minlength="3" ng-required="required" cols="60"></textarea>
      <div class="errors">
        <ul ng-show="serverSide.data.errors.body">
          <li ng-repeat="error in serverSide.data.errors.body">{{error}}</li>
        </ul>
      </div>
    </div>

    <input type="file" ng-file-select="onFileSelect($files)" >
    <div ng-show="dropSupported" class="drop-box" ng-file-drop="onFileSelect($files);" ng-file-drop-available="dropSupported=true">OR drop files here</div>

    <div class="actions">
      <button type="submit" tabindex="100">Submit</button>
    </div>
  </form>

  <h2>Console (Server side)</h2>

  <div ng-show="serverSide.status">
    <div>
      Status: {{ serverSide.status }}
    </div>
    <div>
      Data: <pre>{{ serverSide.data }}</pre>
    </div>

    <div>
      File ID: {{ serverSide.image.id }} <a ng-href="{{ serverSide.image.self }}" target="_blank">{{ serverSide.image.label }}</a>
    </div>

    <div ng-show="serverSide.status == 200">
      New article: <a ng-href="{{ serverSide.data.self }}" target="_blank">{{ serverSide.data.label }}</a>
    </div>
  </div>
</div>
